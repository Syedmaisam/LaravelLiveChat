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
            $count++;
        }

        $this->info("Marked {$count} stale sessions as offline");
        return 0;
    }
}
