<?php

namespace App\Events;

use App\Models\Visitor;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewVisitorNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Visitor $visitor,
        public string $clientName
    ) {}

    public function broadcastOn(): array
    {
        // Broadcast to agents channel for this client
        return [
            new Channel('agents.client.' . $this->visitor->client_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new.visitor';
    }

    public function broadcastWith(): array
    {
        return [
            'visitor_id' => $this->visitor->id,
            'name' => $this->visitor->name ?? 'Anonymous',
            'email' => $this->visitor->email,
            'country' => $this->visitor->country,
            'city' => $this->visitor->city,
            'client_name' => $this->clientName,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
