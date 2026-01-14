<?php

namespace App\Http\Controllers\Dashboard;

use App\Events\AgentJoinedChat;
use App\Events\AgentTyping;
use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function sendMessage(Request $request, Chat $chat)
    {
        $user = Auth::user();

        // Verify agent has access
        if (!$user->clients()->where('clients.id', $chat->client_id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        // Join chat if not already a participant
        $participant = ChatParticipant::firstOrCreate(
            [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
            ],
            [
                'joined_at' => now(),
            ]
        );

        if ($chat->status === 'waiting') {
            $chat->update(['status' => 'active']);
            event(new AgentJoinedChat($chat, $user));
        }

        // Get the sender name at this moment
        $senderName = $user->active_pseudo_name ?? $user->name;
        
        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_type' => 'agent',
            'sender_id' => $user->id,
            'sender_name' => $senderName, // Store name at send time
            'message_type' => 'text',
            'message' => $request->message,
            'is_read' => false,
        ]);

        event(new MessageSent($message));
        
        // Also send proactive message notification to widget (for toast above icon)
        // This ensures visitor sees message even if they haven't opened chat yet
        if ($chat->visitor) {
            \Illuminate\Support\Facades\Log::info("ProactiveMessage Debug: Sending via sendMessage. VisitorKey: {$chat->visitor->visitor_key}");
            event(new \App\Events\ProactiveMessage(
                $chat->visitor,
                $request->message,
                $user->active_pseudo_name ?? $user->name,
                null
            ));
        }

        return response()->json([
            'message' => [
                'id' => $message->id,
                'message' => $message->message,
                'sender_name' => $user->active_pseudo_name ?? $user->name,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ]);
    }

    public function uploadFile(Request $request, Chat $chat)
    {
        $user = Auth::user();

        if (!$user->clients()->where('clients.id', $chat->client_id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
        ]);

        // Join chat if not already a participant
        ChatParticipant::firstOrCreate(
            [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
            ],
            [
                'joined_at' => now(),
            ]
        );

        $file = $request->file('file');
        $path = $file->store("chat-files/{$chat->id}", 'local');
        $filename = time() . '_' . $file->getClientOriginalName();

        // Get the sender name at this moment
        $senderName = $user->active_pseudo_name ?? $user->name;
        
        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_type' => 'agent',
            'sender_id' => $user->id,
            'sender_name' => $senderName, // Store name at send time
            'message_type' => 'file',
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getMimeType(),
            'is_read' => false,
        ]);

        event(new MessageSent($message));

        return response()->json([
            'message' => [
                'id' => $message->id,
                'chat_id' => $chat->id,
                'sender_type' => 'agent',
                'message_type' => 'file',
                'message' => null,
                'file_name' => $message->file_name,
                'file_size' => $message->file_size,
                'file_type' => $message->file_type,
                'file_url' => "/dashboard/chat/{$chat->id}/file/{$message->id}/download",
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ]);
    }

    public function typing(Request $request, Chat $chat)
    {
        $user = Auth::user();

        if (!$user->clients()->where('clients.id', $chat->client_id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        event(new AgentTyping($chat, $user));

        return response()->json(['success' => true]);
    }

    public function joinChat(Chat $chat)
    {
        $user = Auth::user();

        if (!$user->clients()->where('clients.id', $chat->client_id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $participant = ChatParticipant::firstOrCreate(
            [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
            ],
            [
                'joined_at' => now(),
            ]
        );

        if ($chat->status === 'waiting') {
            $chat->update(['status' => 'active']);
        }

        event(new AgentJoinedChat($chat, $user));

        return response()->json(['success' => true, 'joined_at' => $participant->joined_at]);
    }

    public function getMessages(Request $request, Chat $chat)
    {
        $user = Auth::user();

        if (!$user->clients()->where('clients.id', $chat->client_id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $perPage = 50;
        $messages = $chat->messages()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'messages' => $messages->items(),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function downloadFile(Chat $chat, $messageId)
    {
        $user = Auth::user();
        
        // Find message manually - route binding fails with nested params
        $message = Message::findOrFail($messageId);

        if ($message->message_type !== 'file' || !$message->file_path) {
            abort(404);
        }

        // Verify message belongs to this chat
        if ($message->chat_id !== $chat->id) {
            abort(404);
        }

        // Verify agent has access
        if (!$user->clients()->where('clients.id', $chat->client_id)->exists()) {
            abort(403);
        }

        if (!Storage::exists($message->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::download($message->file_path, $message->file_name);
    }

    public function closeChat(Request $request, Chat $chat)
    {
        $user = Auth::user();

        if (!$user->clients()->where('clients.id', $chat->client_id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $chat->update([
            'status' => 'closed',
            'ended_at' => now(),
            'ended_by' => 'agent',
        ]);

        event(new \App\Events\ChatClosed($chat, 'agent'));

        return response()->json(['success' => true]);
    }

    public function updateLabel(Request $request, Chat $chat)
    {
        $user = Auth::user();

        if (!$user->clients()->where('clients.id', $chat->client_id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'label' => 'required|in:new,pending,converted,no_response,closed',
        ]);

        $chat->update(['label' => $request->label]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'label' => $request->label]);
        }

        return back()->with('success', 'Chat label updated');
    }

    // ==================== INTERNAL NOTES ====================

    public function addNote(Request $request, Chat $chat)
    {
        $user = Auth::user();

        if (!$user->clients()->where('clients.id', $chat->client_id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate(['note' => 'required|string|max:2000']);

        $note = \App\Models\ChatNote::create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'note' => $request->note,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'note' => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'user_name' => $user->name,
                    'is_pinned' => $note->is_pinned,
                    'created_at' => $note->created_at->diffForHumans(),
                ],
            ]);
        }

        return back()->with('success', 'Note added');
    }

    public function updateNote(Request $request, Chat $chat, \App\Models\ChatNote $note)
    {
        $user = Auth::user();

        if ($note->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate(['note' => 'required|string|max:2000']);

        $note->update(['note' => $request->note]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Note updated');
    }

    public function deleteNote(Chat $chat, \App\Models\ChatNote $note)
    {
        $user = Auth::user();

        if ($note->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $note->delete();

        return response()->json(['success' => true]);
    }

    public function togglePinNote(Chat $chat, \App\Models\ChatNote $note)
    {
        $user = Auth::user();

        if (!$user->clients()->where('clients.id', $chat->client_id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $note->update(['is_pinned' => !$note->is_pinned]);

        return response()->json(['success' => true, 'is_pinned' => $note->is_pinned]);
    }

    // ==================== PROACTIVE MESSAGES ====================

    /**
     * Send a proactive message to a visitor (agent-initiated chat)
     */
    public function sendProactiveMessage(Request $request, \App\Models\Visitor $visitor)
    {
        \Illuminate\Support\Facades\Log::info("ProactiveMessage Debug: ENTERING method. Visitor: {$visitor->id}, Key: {$visitor->visitor_key}, Client: {$visitor->client_id}");

        $user = Auth::user();

        // Verify agent has access to this visitor's client
        if (!$user->clients()->where('clients.id', $visitor->client_id)->exists()) {
            \Illuminate\Support\Facades\Log::info("ProactiveMessage Debug: Unauthorized. User {$user->id} cannot access Client {$visitor->client_id}");
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        // Find or create an active chat with this visitor
        $chat = \App\Models\Chat::where('visitor_id', $visitor->id)
            ->whereIn('status', ['waiting', 'active'])
            ->first();

        if (!$chat) {
            // Create a new chat
            $chat = \App\Models\Chat::create([
                'client_id' => $visitor->client_id,
                'visitor_id' => $visitor->id,
                'visitor_session_id' => $visitor->sessions()->latest()->first()?->id,
                'status' => 'active',
                'label' => 'new',
                'last_message_at' => now(),
            ]);
        }

        // Join the chat
        ChatParticipant::firstOrCreate(
            ['chat_id' => $chat->id, 'user_id' => $user->id],
            ['joined_at' => now()]
        );

        // Get the sender name at this moment
        $senderName = $user->active_pseudo_name ?? $user->name;
        
        // Create the message
        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_type' => 'agent',
            'sender_id' => $user->id,
            'sender_name' => $senderName, // Store name at send time
            'message_type' => 'text',
            'message' => $request->message,
            'is_read' => false,
        ]);

        // Update chat
        $chat->update([
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        // Broadcast to the chat channel (for when visitor opens chat)
        event(new MessageSent($message));

        // Also broadcast a proactive message notification to visitor's widget
        \Illuminate\Support\Facades\Log::info("ProactiveMessage Debug: Broadcasting to visitor.{$visitor->visitor_key}. Message: {$request->message}");
        
        event(new \App\Events\ProactiveMessage(
            $visitor,
            $request->message,
            $user->active_pseudo_name ?? $user->name,
            null // avatar
        ));

        return response()->json([
            'success' => true,
            'chat_id' => $chat->id,
            'message_id' => $message->id,
        ]);
    }
}
