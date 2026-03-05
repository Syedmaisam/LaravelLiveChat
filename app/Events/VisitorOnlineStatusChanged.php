<?php

namespace App\Events;

use App\Models\VisitorSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitorOnlineStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public VisitorSession $session,
        public bool $isOnline
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('visitors.' . $this->session->client_id),
            new Channel('monitoring'),
            new Channel('visitor-session.' . $this->session->id), // For real-time chat view updates
        ];
    }

    public function broadcastAs(): string
    {
        return 'status.changed'; // Match what chat view listens for
    }

    public function broadcastWith(): array
    {
        return [
            'visitor_id' => $this->session->visitor_id,
            'session_id' => $this->session->id,
            'is_online' => $this->isOnline,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
