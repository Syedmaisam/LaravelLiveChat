<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Events\VisitorTyping;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Client;
use App\Models\Message;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /**
     * Check if visitor has an existing chat (e.g., agent-initiated)
     * Returns chat info and messages if exists
     */
    public function checkExisting(Request $request)
    {
        $request->validate([
            'widget_key' => 'required|string',
            'visitor_key' => 'required|uuid',
        ]);

        $client = Client::where('widget_key', $request->widget_key)
            ->where('is_active', true)
            ->first();

        if (!$client) {
            return response()->json(['exists' => false]);
        }

        $visitor = Visitor::where('visitor_key', $request->visitor_key)
            ->where('client_id', $client->id)
            ->first();

        if (!$visitor) {
            return response()->json(['exists' => false]);
        }

        // Check for existing active/waiting chat
        $chat = Chat::where('visitor_id', $visitor->id)
            ->where('client_id', $client->id)
            ->whereIn('status', ['waiting', 'active'])
            ->first();

        if (!$chat) {
            return response()->json(['exists' => false]);
        }

        // Load messages with sender info
        $messages = $chat->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                $data = $message->toArray();
                if ($message->sender_type === 'agent') {
                    // Use stored sender_name if available (for dynamic pseudo name),
                    // otherwise fallback to current name
                    $data['sender_name'] = $message->sender_name 
                        ?? ($message->sender ? ($message->sender->active_pseudo_name ?? $message->sender->name) : 'Agent');
                }
                return $data;
            });

        return response()->json([
            'exists' => true,
            'chat_id' => $chat->id,
            'status' => $chat->status,
            'messages' => $messages,
        ]);
    }

    public function create(Request $request)

    {
        $request->validate([
            'widget_key' => 'required|string',
            'visitor_key' => 'required|uuid',
            'session_key' => 'nullable|uuid',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'nullable|string|max:5000',
        ]);

        $client = Client::where('widget_key', $request->widget_key)
            ->where('is_active', true)
            ->firstOrFail();

        $visitor = Visitor::where('visitor_key', $request->visitor_key)
            ->where('client_id', $client->id)
            ->firstOrFail();

        $visitor->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        $session = null;
        if ($request->session_key) {
            $session = $visitor->sessions()
                ->where('session_key', $request->session_key)
                ->first();
        }

        // Check if visitor already has an active/waiting chat (e.g., admin-initiated)
        $existingChat = Chat::where('visitor_id', $visitor->id)
            ->where('client_id', $client->id)
            ->whereIn('status', ['waiting', 'active'])
            ->first();

        if ($existingChat) {
            // Use existing chat (update visitor info and session if needed)
            $chat = $existingChat;
            if ($session && !$chat->visitor_session_id) {
                $chat->update(['visitor_session_id' => $session->id]);
            }
            $chat->update(['lead_form_filled' => true]);
        } else {
            // Create new chat
            $chat = Chat::create([
                'client_id' => $client->id,
                'visitor_id' => $visitor->id,
                'visitor_session_id' => $session?->id,
                'status' => 'waiting',
                'lead_form_filled' => true,
                'started_at' => now(),
            ]);
        }

        // If a message was included, create the first message
        if ($request->message) {
            $message = Message::create([
                'chat_id' => $chat->id,
                'sender_type' => 'visitor',
                'sender_id' => $visitor->id,
                'message_type' => 'text',
                'message' => $request->message,
                'is_read' => false,
            ]);
            
            event(new MessageSent($message));
        }

        return response()->json([
            'chat_id' => $chat->id,
            'status' => $chat->status,
        ]);
    }

    public function sendMessage(Request $request, Chat $chat)
    {
        $request->validate([
            'visitor_key' => 'required|uuid',
            'message' => 'required|string|max:5000',
        ]);

        // Verify visitor owns this chat
        if ($chat->visitor->visitor_key !== $request->visitor_key) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_type' => 'visitor',
            'sender_id' => $chat->visitor_id,
            'message_type' => 'text',
            'message' => $request->message,
            'is_read' => false,
        ]);

        // Update chat tracking
        $chat->update([
            'status' => $chat->status === 'waiting' ? 'active' : $chat->status,
            'last_message_at' => now(),
            'unread_count' => $chat->unread_count + 1,
            'label' => $chat->label ?? 'new',
        ]);

        // Update visitor session activity to keep them online
        if ($chat->visitor_session_id) {
            $chat->visitorSession()->update([
                'last_activity_at' => now(),
                'is_online' => true
            ]);
        }

        event(new MessageSent($message));

        // Notify agents
        $agentIds = [];
        if ($chat->participants()->exists()) {
            $agentIds = $chat->participants()->pluck('users.id')->toArray();
        } else {
            // Notify all agents assigned to this client
            $agentIds = $chat->client->agents()->pluck('users.id')->toArray();
        }

        \Log::info('Notifying agents', ['agent_ids' => $agentIds, 'chat_id' => $chat->id]);

        foreach ($agentIds as $agentId) {
            event(new \App\Events\AgentNotification(
                $agentId,
                'New Message',
                "From {$chat->visitor->name}",
                "/dashboard/chat/{$chat->id}"
            ));
        }

        return response()->json([
            'message' => [
                'id' => $message->id,
                'message' => $message->message,
                'sender_type' => 'visitor',
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ]);
    }

    public function typing(Request $request, Chat $chat)
    {
        $request->validate([
            'visitor_key' => 'required|uuid',
        ]);

        if ($chat->visitor->visitor_key !== $request->visitor_key) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        event(new VisitorTyping($chat, $chat->visitor));

        return response()->json(['success' => true]);
    }

    public function getMessages(Request $request, Chat $chat)
    {
        $request->validate([
            'visitor_key' => 'required|uuid',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($chat->visitor->visitor_key !== $request->visitor_key) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $perPage = 50;
        $messages = $chat->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        // Format messages to include sender_name
        $formattedMessages = $messages->getCollection()->map(function ($message) {
            $data = $message->toArray();
            if ($message->sender_type === 'agent') {
                // Use stored sender_name if available (for dynamic pseudo name),
                // otherwise fallback to current name
                $data['sender_name'] = $message->sender_name 
                    ?? ($message->sender ? ($message->sender->active_pseudo_name ?? $message->sender->name) : 'Agent');
            }
            return $data;
        });

        return response()->json([
            'messages' => $formattedMessages,
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function uploadFile(Request $request, Chat $chat)
    {
        $request->validate([
            'visitor_key' => 'required|uuid',
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
        ]);

        if ($chat->visitor->visitor_key !== $request->visitor_key) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $file = $request->file('file');
        $path = $file->store("chat-files/{$chat->id}", 'local');
        $filename = time() . '_' . $file->getClientOriginalName();

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_type' => 'visitor',
            'sender_id' => $chat->visitor_id,
            'message_type' => 'file',
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getMimeType(),
            'is_read' => false,
        ]);

        // Update visitor session activity to keep them online
        if ($chat->visitor_session_id) {
            $chat->visitorSession()->update([
                'last_activity_at' => now(),
                'is_online' => true
            ]);
        }

        event(new MessageSent($message));

        // Notify agents
        $agentIds = [];
        if ($chat->participants()->exists()) {
            $agentIds = $chat->participants()->pluck('users.id')->toArray();
        } else {
            // Notify all agents assigned to this client
            $agentIds = $chat->client->agents()->pluck('users.id')->toArray();
        }

        foreach ($agentIds as $agentId) {
            event(new \App\Events\AgentNotification(
                $agentId,
                'New File Upload',
                "From {$chat->visitor->name}",
                "/inbox/{$chat->uuid}"
            ));
        }

        return response()->json([
            'message' => [
                'id' => $message->id,
                'chat_id' => $chat->id,
                'sender_type' => 'visitor',
                'message_type' => 'file',
                'message' => null,
                'file_name' => $message->file_name,
                'file_size' => $message->file_size,
                'file_type' => $message->file_type,
                'file_url' => "/api/chat/{$chat->id}/file/{$message->id}/download",
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Agent sends a message (authenticated via session)
     */
    public function agentSendMessage(Request $request, Chat $chat)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $user = $request->user();
        
        // Verify agent has access to this chat's client
        if (!$user->clients()->where('clients.id', $chat->client_id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get the current pseudo name for this agent
        $senderName = $user->active_pseudo_name ?? $user->name;
        
        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_type' => 'agent',
            'sender_id' => $user->id,
            'sender_name' => $senderName, // Store name at send time for dynamic pseudo name support
            'message_type' => 'text',
            'message' => $request->message,
            'is_read' => false,
        ]);

        event(new MessageSent($message));

        // Proactive Event for Toast Notification (when chat closed)
        if ($chat->visitor) {
            event(new \App\Events\ProactiveMessage(
                $chat->visitor,
                $request->message,
                $senderName,
                null
            ));
        }

        return response()->json([
            'message' => [
                'id' => $message->id,
                'message' => $message->message,
                'sender_type' => 'agent',
                'sender_name' => $user->active_pseudo_name ?? $user->name,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Agent typing indicator
     */
    public function agentTyping(Request $request, Chat $chat)
    {
        $user = $request->user();
        
        if (!$user->clients()->where('clients.id', $chat->client_id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        event(new \App\Events\AgentTyping($chat, $user));

        return response()->json(['success' => true]);
    }

    /**
     * Mark messages as read (called by widget when visitor reads agent messages)
     */
    public function markAsRead(Request $request, Chat $chat)
    {
        $request->validate([
            'visitor_key' => 'required|string',
        ]);

        // Verify visitor owns this chat
        $visitor = Visitor::where('visitor_key', $request->visitor_key)->first();
        if (!$visitor || $chat->visitor_id !== $visitor->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Mark all agent messages as read
        $unreadMessages = $chat->messages()
            ->where('sender_type', 'agent')
            ->where('is_read', false)
            ->get();

        if ($unreadMessages->count() > 0) {
            $messageIds = $unreadMessages->pluck('id')->toArray();
            
            Message::whereIn('id', $messageIds)->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            // Broadcast read receipt to admin
            event(new \App\Events\MessagesRead($chat, $messageIds, 'visitor'));
        }

        return response()->json(['success' => true, 'count' => $unreadMessages->count()]);
    }

    /**
     * Download a file from a chat message
     */
    public function downloadFile(Request $request, Chat $chat, Message $message)
    {
        // visitor_key is optional for GET requests (comes from query string)
        $visitorKey = $request->query('visitor_key') ?? $request->input('visitor_key');

        // Verify visitor owns this chat (if visitor_key provided)
        if ($visitorKey) {
            $visitor = Visitor::where('visitor_key', $visitorKey)->first();
            if (!$visitor || $chat->visitor_id !== $visitor->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        // Verify message belongs to this chat
        if ($message->chat_id !== $chat->id) {
            return response()->json(['error' => 'Message not found in this chat'], 404);
        }

        // Verify it's a file message
        if ($message->message_type !== 'file' || !$message->file_path) {
            return response()->json(['error' => 'Not a file message'], 400);
        }

        // Check if file exists using Storage facade
        if (!\Storage::exists($message->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        // Serve the file using Storage facade
        return \Storage::download($message->file_path, $message->file_name);
    }

    /**
     * Submit chat rating and feedback
     */
    public function rateChat(Request $request, Chat $chat)
    {
        $request->validate([
            'visitor_key' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
        ]);

        // Verify visitor owns this chat
        $visitor = Visitor::where('visitor_key', $request->visitor_key)->first();
        if (!$visitor || $chat->visitor_id !== $visitor->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if already rated
        if ($chat->rating) {
            return response()->json(['error' => 'Chat already rated'], 400);
        }

        $rating = \App\Models\ChatRating::create([
            'chat_id' => $chat->id,
            'visitor_id' => $visitor->id,
            'rating' => $request->rating,
            'feedback' => $request->feedback,
        ]);

        return response()->json([
            'success' => true,
            'rating' => $rating->rating,
        ]);
    }

    public function updateNickname(Request $request, Chat $chat)
    {
        $validated = $request->validate([
            'nickname' => 'required|string|max:100'
        ]);

        $user = $request->user();

        // Update global active pseudo name
        $user->update(['active_pseudo_name' => $validated['nickname']]);

        // Update or attach participant with nickname
        $chat->participants()->syncWithoutDetaching([
            $user->id => ['agent_nickname' => $validated['nickname']]
        ]);

        return response()->json(['success' => true]);
    }
}
