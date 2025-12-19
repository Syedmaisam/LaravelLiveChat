<?php

namespace App\Events;

use App\Models\VisitorSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitorOnlineStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public VisitorSession $session,
        public bool $isOnline
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('visitor.' . $this->session->visitor_id),
            new PrivateChannel('monitoring'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'visitor.status.changed';
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
