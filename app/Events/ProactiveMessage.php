<?php

namespace App\Events;

use App\Models\Visitor;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProactiveMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Visitor $visitor,
        public string $message,
        public string $agentName,
        public ?string $agentAvatar = null
    ) {
        \Illuminate\Support\Facades\Log::info("ProactiveMessage Debug: Event Constructed for Visitor Key: {$visitor->visitor_key}");
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('visitor.' . $this->visitor->visitor_key),
        ];
    }

    public function broadcastAs(): string
    {
        return 'proactive.message';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'agent_name' => $this->agentName,
            'agent_avatar' => $this->agentAvatar,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
