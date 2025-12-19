<?php

namespace App\Events;

use App\Models\Chat;
use App\Models\ChatTransfer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatTransferred implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Chat $chat,
        public ChatTransfer $transfer
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->chat->id),
            new PrivateChannel('agent.' . $this->transfer->to_agent_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.transferred';
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chat->id,
            'transfer' => [
                'from_agent_id' => $this->transfer->from_agent_id,
                'to_agent_id' => $this->transfer->to_agent_id,
                'reason' => $this->transfer->reason,
                'transferred_at' => $this->transfer->transferred_at->toIso8601String(),
            ],
        ];
    }
}
