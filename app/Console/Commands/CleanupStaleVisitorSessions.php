<?php

namespace App\Console\Commands;

use App\Events\VisitorOnlineStatusChanged;
use App\Models\VisitorSession;
use Illuminate\Console\Command;

class CleanupStaleVisitorSessions extends Command
{
    protected $signature = 'visitors:cleanup-stale';

    protected $description = 'Mark stale visitor sessions as offline';

    public function handle()
    {
        $oneMinuteAgo = now()->subMinutes(1);

        $staleSessions = VisitorSession::where('is_online', true)
            ->where('last_activity_at', '<', $oneMinuteAgo)
            ->get();

        $count = 0;
        foreach ($staleSessions as $session) {
            $session->update([
                'is_online' => false,
            ]);

            event(new VisitorOnlineStatusChanged($session, false));

            // Auto-close waiting chats (no agent has joined yet)
            $waitingChats = \App\Models\Chat::where('visitor_session_id', $session->id)
                ->where('status', 'waiting')
                ->get();

            foreach ($waitingChats as $chat) {
                $chat->update([
                    'status' => 'closed',
                    'ended_at' => now(),
                    'ended_by' => 'visitor',
                ]);
                event(new \App\Events\ChatClosed($chat, 'visitor'));
            }

            $count++;
        }

        $this->info("Marked {$count} stale sessions as offline");

        return 0;
    }
}
