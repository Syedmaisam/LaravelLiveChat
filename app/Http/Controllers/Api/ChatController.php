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

        $chat = Chat::create([
            'client_id' => $client->id,
            'visitor_id' => $visitor->id,
            'visitor_session_id' => $session?->id,
            'status' => 'waiting',
            'lead_form_filled' => true,
            'started_at' => now(),
        ]);

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

        if ($chat->status === 'waiting') {
            $chat->update(['status' => 'active']);
        }

        event(new MessageSent($message));

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
            ->orderBy('created_at', 'asc')
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

        event(new MessageSent($message));

        return response()->json([
            'message' => [
                'id' => $message->id,
                'file_name' => $message->file_name,
                'file_size' => $message->file_size,
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

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_type' => 'agent',
            'sender_id' => $user->id,
            'message_type' => 'text',
            'message' => $request->message,
            'is_read' => false,
        ]);

        event(new MessageSent($message));

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
}
