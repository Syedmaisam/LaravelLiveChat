<?php

namespace App\Events;

use App\Models\Chat;
use App\Models\Visitor;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitorTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Chat $chat,
        public Visitor $visitor
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('chat.' . $this->chat->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'visitor.typing';
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chat->id,
            'visitor_id' => $this->visitor->id,
            'visitor_name' => $this->visitor->name ?? 'Visitor',
        ];
    }
}
