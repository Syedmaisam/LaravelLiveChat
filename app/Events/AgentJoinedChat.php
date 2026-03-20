<?php

namespace App\Events;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentJoinedChat implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Chat $chat,
        public User $agent
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
        return 'agent.joined';
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chat->id,
            'session_id' => $this->chat->visitor_session_id,
            'agent' => [
                'id' => $this->agent->id,
                'name' => $this->agent->active_pseudo_name ?? $this->agent->name,
                'avatar' => $this->agent->avatar,
            ],
        ];
    }
}
