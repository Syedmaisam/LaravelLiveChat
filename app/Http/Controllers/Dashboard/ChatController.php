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
                'sender_name' => $user->pseudo_name ?? $user->name,
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

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_type' => 'agent',
            'sender_id' => $user->id,
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

    public function downloadFile(Message $message)
    {
        $user = Auth::user();

        if ($message->message_type !== 'file' || !$message->file_path) {
            abort(404);
        }

        $chat = $message->chat;

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
}
