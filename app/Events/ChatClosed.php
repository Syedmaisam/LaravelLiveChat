<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatClosed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Chat $chat,
        public string $endedBy
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('chat.'.$this->chat->id),
            new Channel('monitoring'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.closed';
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chat->id,
            'ended_by' => $this->endedBy,
            'ended_at' => $this->chat->ended_at?->toIso8601String(),
        ];
    }
}
