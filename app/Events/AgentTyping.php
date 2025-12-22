<?php

namespace App\Events;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Chat $chat,
        public User $agent
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('chat.' . $this->chat->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'agent.typing';
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chat->id,
            'agent_id' => $this->agent->id,
            'agent_name' => $this->agent->pseudo_name ?? $this->agent->name,
        ];
    }
}
