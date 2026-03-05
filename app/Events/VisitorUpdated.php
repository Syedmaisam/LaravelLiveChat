<?php

namespace App\Events;

use App\Models\Chat;
use App\Models\Visitor;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitorUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Visitor $visitor,
        public Chat $chat
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('monitoring'),
            new Channel('chat.' . $this->chat->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'visitor.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'visitor' => [
                'id' => $this->visitor->id,
                'name' => $this->visitor->name,
                'email' => $this->visitor->email,
                'phone' => $this->visitor->phone,
                'location' => [
                    'country' => $this->visitor->country,
                    'city' => $this->visitor->city,
                ],
            ],
            'chat' => [
                'id' => $this->chat->id,
                'status' => $this->chat->status,
                'client_id' => $this->chat->client_id,
                'client_name' => $this->chat->client->name,
                'created_at' => $this->chat->created_at->toIso8601String(),
                'updated_at' => $this->chat->updated_at->toIso8601String(),
            ],
        ];
    }
}
