<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public int $clientId
    ) {}

    public function broadcastOn(): array
    {
        // Broadcast to agents channel for this client
        return [
            new Channel('agents.client.' . $this->clientId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new.message';
    }

    public function broadcastWith(): array
    {
        $chat = $this->message->chat;
        $visitor = $chat->visitor;
        
        return [
            'chat_id' => $chat->id,
            'message_id' => $this->message->id,
            'message' => $this->message->message,
            'sender_type' => $this->message->sender_type,
            'visitor_name' => $visitor->name ?? 'Anonymous',
            'visitor_email' => $visitor->email,
            'client_id' => $this->clientId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
