<?php

namespace App\Events;

use App\Models\Visitor;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitorJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Visitor $visitor
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('visitors.' . $this->visitor->client_id),
            new PrivateChannel('monitoring'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'visitor.joined';
    }

    public function broadcastWith(): array
    {
        return [
            'visitor' => [
                'id' => $this->visitor->id,
                'name' => $this->visitor->name,
                'client_id' => $this->visitor->client_id,
                'device' => $this->visitor->device,
                'location' => [
                    'country' => $this->visitor->country,
                    'city' => $this->visitor->city,
                ],
            ],
        ];
    }
}
