<?php

namespace App\Events;

use App\Models\VisitorSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitorPageChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public VisitorSession $session,
        public string $pageUrl,
        public ?string $pageTitle = null
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
        return 'visitor.page.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'visitor_id' => $this->session->visitor_id,
            'session_id' => $this->session->id,
            'page_url' => $this->pageUrl,
            'page_title' => $this->pageTitle,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
