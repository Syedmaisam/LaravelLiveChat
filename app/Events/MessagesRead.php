<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessagesRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Chat $chat,
        public array $messageIds,
        public string $readBy // 'agent' or 'visitor'
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('chat.' . $this->chat->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'messages.read';
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chat->id,
            'message_ids' => $this->messageIds,
            'read_by' => $this->readBy,
            'read_at' => now()->toIso8601String(),
        ];
    }
}
