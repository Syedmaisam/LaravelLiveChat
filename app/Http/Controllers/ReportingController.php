<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Message;
use App\Models\VisitorSession;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportingController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->get('range', '7'); // Days
        $startDate = Carbon::now()->subDays($range);

        // 1. Chats per day (Line Chart)
        $chatsPerDay = Chat::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 2. Average Response Time (Mock calculation for now, needing proper message timestamps diffs)
        // In a real scenario, we'd calculate avg time between visitor message and next agent message.
        $avgResponseTime = 45; // seconds (placeholder)

        // 3. Visitor Stats
        $totalVisitors = VisitorSession::where('started_at', '>=', $startDate)->count();

        // 4. Agent Leaderboard
        // Count chats where agent participated
        $agentStats = DB::table('users')
            ->join('chat_participants', 'users.id', '=', 'chat_participants.user_id')
            ->join('chats', 'chat_participants.chat_id', '=', 'chats.id')
            ->where('chats.created_at', '>=', $startDate)
            ->select('users.name', DB::raw('count(chats.id) as chat_count'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('chat_count')
            ->limit(5)
            ->get();

        return view('dashboard.reporting.index', compact('chatsPerDay', 'avgResponseTime', 'totalVisitors', 'agentStats', 'range'));
    }
}
