<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Client;
use App\Models\Visitor;
use App\Models\VisitorSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    private function getCommonData()
    {
        $user = Auth::user();
        $clientIds = $user->clients()->pluck('clients.id');

        // All chats for sidebar (ordered by last activity)
        $allChats = Chat::whereIn('client_id', $clientIds)
            ->with(['visitor', 'client', 'visitorSession', 'messages' => function ($q) {
                $q->orderBy('created_at', 'desc')->limit(1);
            }])
            ->orderByRaw("CASE WHEN status = 'waiting' THEN 0 WHEN status = 'active' THEN 1 ELSE 2 END")
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($chat) {
                $lastMsg = $chat->messages->first();
                return [
                    'id' => $chat->id,
                    'visitor_name' => $chat->visitor->name ?? 'Anonymous',
                    'visitor_email' => $chat->visitor->email,
                    'client_name' => $chat->client->name,
                    'status' => $chat->status,
                    'label' => $chat->label,
                    'unread_count' => $chat->unread_count ?? 0,
                    'last_message' => $lastMsg?->message ? \Str::limit($lastMsg->message, 50) : null,
                    'last_message_at' => $chat->last_message_at ?? $chat->updated_at,
                    'is_online' => $chat->visitorSession?->is_online ?? false,
                    'started_at' => $chat->started_at,
                ];
            });

        $waitingChats = Chat::whereIn('client_id', $clientIds)
            ->where('status', 'waiting')
            ->with(['visitor', 'client'])
            ->orderBy('started_at')
            ->get();

        $activeChats = Chat::whereIn('client_id', $clientIds)
            ->where('status', 'active')
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->with(['visitor', 'client', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->get();
            
        return compact('waitingChats', 'activeChats', 'clientIds', 'allChats');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $data = $this->getCommonData();
        
        $clientIds = $data['clientIds'];
        $onlineVisitors = VisitorSession::whereIn('client_id', $clientIds)
            ->where('is_online', true)
            ->with(['visitor', 'client'])
            ->latest('last_activity_at')
            ->get();

        $clients = $user->clients()->get();

        return view('dashboard.index', array_merge($data, [
            'onlineVisitors' => $onlineVisitors,
            'clients' => $clients,
            'filters' => $request->only(['search', 'status', 'client_id']),
            'currentChat' => null
        ]));
    }

    public function monitoring()
    {
        $user = Auth::user();
        $clientIds = $user->clients()->pluck('clients.id');

        $onlineVisitors = VisitorSession::whereIn('client_id', $clientIds)
            ->where('is_online', true)
            ->with(['visitor', 'client'])
            ->latest('last_activity_at')
            ->get();

        return view('dashboard.monitoring', [
            'onlineVisitors' => $onlineVisitors,
        ]);
    }

    public function chat(Chat $chat)
    {
        $user = Auth::user();

        // Verify agent has access
        if (!$user->clients()->where('clients.id', $chat->client_id)->exists()) {
            abort(403);
        }

        // Load recent messages (last 50) - ordered oldest to newest
        $messages = $chat->messages()
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get();

        // Load chat participants
        $participants = $chat->participants()->with('user')->get();

        // Get file messages (media)
        $files = $chat->messages()->where('message_type', 'file')->get();

        // Get visitor's page visits from session
        $pageVisits = $chat->visitorSession?->pageVisits()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get() ?? collect();

        return view('dashboard.chat', [
            'chat' => $chat->load(['visitor', 'client', 'visitorSession']),
            'messages' => $messages,
            'participants' => $participants,
            'files' => $files,
            'pageVisits' => $pageVisits,
            'hasMoreMessages' => $chat->messages()->count() > 50,
        ]);
    }

    /**
     * Agent-initiated chat with a visitor
     */
    public function initiateChat(Request $request)
    {
        $user = Auth::user();
        $session = VisitorSession::with(['visitor', 'client'])->findOrFail($request->session_id);

        // Verify agent has access to this client
        if (!$user->clients()->where('clients.id', $session->client_id)->exists()) {
            abort(403, 'You are not assigned to this client.');
        }

        // Check if visitor already has an active chat
        $existingChat = Chat::where('visitor_id', $session->visitor_id)
            ->where('client_id', $session->client_id)
            ->whereIn('status', ['waiting', 'active'])
            ->first();

        if ($existingChat) {
            // Join existing chat
            $chat = $existingChat;
        } else {
            // Create new chat
            $chat = Chat::create([
                'client_id' => $session->client_id,
                'visitor_id' => $session->visitor_id,
                'visitor_session_id' => $session->id,
                'status' => 'active',
                'lead_form_filled' => $session->visitor->email ? true : false,
                'started_at' => now(),
            ]);
        }

        // Add agent as participant if not already
        if (!$chat->participants()->where('user_id', $user->id)->exists()) {
            $chat->participants()->attach($user->id, [
                'joined_at' => now(),
            ]);
        }

        // Update chat status to active
        $chat->update(['status' => 'active']);

        return redirect()->route('dashboard.chat', $chat);
    }
}
