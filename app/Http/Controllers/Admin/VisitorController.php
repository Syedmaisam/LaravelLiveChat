<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\VisitorSession;
use App\Models\Chat;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::where('is_active', true)->get();
        $clientId = $request->get('client');
        $tab = $request->get('tab', 'active');

        $query = VisitorSession::with(['visitor', 'client', 'pageVisits'])
            ->when($clientId, fn($q) => $q->where('client_id', $clientId));

        if ($tab === 'active') {
            $visitors = $query->where('is_online', true)
                ->orderBy('last_activity_at', 'desc')
                ->paginate(50);
        } else {
            $visitors = $query->where('is_online', false)
                ->orderBy('ended_at', 'desc')
                ->paginate(50);
        }

        $activeCount = VisitorSession::where('is_online', true)
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->count();

        $historyCount = VisitorSession::where('is_online', false)
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->count();

        return view('admin.visitors.index', compact(
            'visitors', 'clients', 'clientId', 'tab', 'activeCount', 'historyCount'
        ));
    }

    public function show(VisitorSession $session)
    {
        $session->load(['visitor', 'client', 'pageVisits', 'chats.messages']);
        
        return response()->json([
            'id' => $session->id,
            'visitor' => [
                'name' => $session->visitor->name ?? 'Anonymous',
                'email' => $session->visitor->email,
                'phone' => $session->visitor->phone,
            ],
            'client' => $session->client->name,
            'ip' => $session->ip_address,
            'country' => $session->visitor->country,
            'city' => $session->visitor->city,
            'device' => $session->visitor->device,
            'browser' => $session->visitor->browser,
            'os' => $session->visitor->os,
            'referrer' => $session->referrer,
            'current_page' => $session->current_page,
            'is_online' => $session->is_online,
            'started_at' => $session->started_at->format('H:i:s'),
            'duration' => $session->started_at->diffForHumans(null, true),
            'page_views' => $session->pageVisits->map(fn($pv) => [
                'url' => $pv->url,
                'title' => $pv->title,
                'time' => $pv->created_at->format('H:i:s'),
            ]),
            'has_chat' => $session->chats->count() > 0,
            'chat_id' => $session->chats->first()?->id,
        ]);
    }
}
