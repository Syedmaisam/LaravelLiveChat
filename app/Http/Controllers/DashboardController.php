<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Client;
use App\Models\Visitor;
use App\Models\VisitorSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private function getCommonData()
    {
        $user = Auth::user();
        $clientIds = $user->clients()->pluck('clients.id');
        $request = request();

        // Base query for sidebar chats
        $query = Chat::whereIn('client_id', $clientIds)
            ->with(['visitor', 'client', 'visitorSession', 'messages' => function ($q) {
                $q->orderBy('created_at', 'desc')->limit(1);
            }]);

        // Apply Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('visitor', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Apply Client Filter
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Apply Country Filter
        if ($request->filled('country')) {
            $country = $request->country;
            $query->whereHas('visitor', function ($q) use ($country) {
                $q->where('country_code', $country);
            });
        }

        // All chats for sidebar (ordered by last activity)
        $allChats = $query->orderByRaw("CASE WHEN status = 'waiting' THEN 0 WHEN status = 'active' THEN 1 ELSE 2 END")
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($chat) {
                $lastMsg = $chat->messages->first();
                return [
                    'id' => $chat->id,
                    'uuid' => $chat->uuid,
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
                    'country_code' => $chat->visitor->country_code ?? null,
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

        // Get Available Clients with Chat Counts
        $clients = Client::whereIn('id', $clientIds)
            ->withCount('chats')
            ->get();

        // Get Available Countries with Chat Counts
        $countries = Chat::whereIn('chats.client_id', $clientIds)
            ->join('visitors', 'chats.visitor_id', '=', 'visitors.id')
            ->whereNotNull('visitors.country_code')
            ->select('visitors.country_code', 'visitors.country', DB::raw('count(chats.id) as total'))
            ->groupBy('visitors.country_code', 'visitors.country')
            ->orderByDesc('total')
            ->get();
            
        return compact('waitingChats', 'activeChats', 'clientIds', 'allChats', 'countries', 'clients');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $clientIds = $user->clients()->pluck('clients.id');

        // Active visitors (all online sessions, with or without chats)
        $activeVisitorsQuery = VisitorSession::whereIn('client_id', $clientIds)
            ->where('is_online', true)
            ->with(['visitor', 'chats' => function($q) {
                $q->latest('last_message_at')->limit(1);
            }]);

        // Recent Chats Query
        $recentChatsQuery = Chat::whereIn('client_id', $clientIds)
            ->with(['visitor', 'visitorSession', 'client']);

        // Apply Search
        if ($request->filled('search')) {
            $search = $request->search;
            $recentChatsQuery->whereHas('visitor', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
            // Also filter active visitors if searching
             $activeVisitorsQuery->whereHas('visitor', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply Client Filter
        if ($request->filled('client_id')) {
            $recentChatsQuery->where('client_id', $request->client_id);
            $activeVisitorsQuery->where('client_id', $request->client_id);
        }

        // Apply Country Filter
        if ($request->filled('country')) {
            $country = $request->country;
            $recentChatsQuery->whereHas('visitor', function ($q) use ($country) {
                $q->where('country_code', $country);
            });
             $activeVisitorsQuery->whereHas('visitor', function ($q) use ($country) {
                $q->where('country_code', $country);
            });
        }

        $activeVisitors = $activeVisitorsQuery->latest('last_activity_at')->get();

        $recentChats = $recentChatsQuery->latest('last_message_at')
            ->limit(50)
            ->get();

        // Filter Data with Counts
        $clients = Client::whereIn('id', $clientIds)
            ->withCount('chats')
            ->get();

        $countries = Chat::whereIn('chats.client_id', $clientIds)
            ->join('visitors', 'chats.visitor_id', '=', 'visitors.id')
            ->whereNotNull('visitors.country_code')
            ->select('visitors.country_code', 'visitors.country', DB::raw('count(chats.id) as total'))
            ->groupBy('visitors.country_code', 'visitors.country')
            ->orderByDesc('total')
            ->get();

        return view('dashboard.inbox', [
            'chat' => null,
            'messages' => collect(),
            'participants' => collect(),
            'files' => collect(),
            'pageVisits' => collect(),
            'hasMoreMessages' => false,
            'activeVisitors' => $activeVisitors,
            'recentChats' => $recentChats,
            'clients' => $clients,
            'countries' => $countries,
        ]);
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
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get()
            ->sortBy('id')
            ->values();

        // Load chat participants
        $participants = $chat->participants()->get();

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

    public function inbox(Chat $chat)
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
        $participants = $chat->participants()->get();

        // Get file messages (media)
        $files = $chat->messages()->where('message_type', 'file')->get();

        // Get visitor's page visits from session
        $pageVisits = $chat->visitorSession?->pageVisits()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get() ?? collect();

        // Load sidebar data
        $clientIds = $user->clients()->pluck('clients.id');

        // Active visitors (all online sessions, with or without chats)
        $activeVisitorsQuery = VisitorSession::whereIn('client_id', $clientIds)
            ->where('is_online', true)
            ->with(['visitor', 'chats' => function($q) {
                $q->latest('last_message_at')->limit(1);
            }]);

        // Recent Chats Query
        $recentChatsQuery = Chat::whereIn('client_id', $clientIds)
            ->with(['visitor', 'visitorSession', 'client']);

        $request = request();

        // Apply Search
        if ($request->filled('search')) {
            $search = $request->search;
            $recentChatsQuery->whereHas('visitor', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
            // Also filter active visitors if searching
             $activeVisitorsQuery->whereHas('visitor', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply Client Filter
        if ($request->filled('client_id')) {
            $recentChatsQuery->where('client_id', $request->client_id);
            $activeVisitorsQuery->where('client_id', $request->client_id);
        }

        // Apply Country Filter
        if ($request->filled('country')) {
            $country = $request->country;
            $recentChatsQuery->whereHas('visitor', function ($q) use ($country) {
                $q->where('country_code', $country);
            });
             $activeVisitorsQuery->whereHas('visitor', function ($q) use ($country) {
                $q->where('country_code', $country);
            });
        }

        $activeVisitors = $activeVisitorsQuery->latest('last_activity_at')->get();

        $recentChats = $recentChatsQuery->latest('last_message_at')
            ->limit(50)
            ->get();

        // Filter Data with Counts
        $clients = Client::whereIn('id', $clientIds)
            ->withCount('chats')
            ->get();

        $countries = Chat::whereIn('chats.client_id', $clientIds)
            ->join('visitors', 'chats.visitor_id', '=', 'visitors.id')
            ->whereNotNull('visitors.country_code')
            ->select('visitors.country_code', 'visitors.country', DB::raw('count(chats.id) as total'))
            ->groupBy('visitors.country_code', 'visitors.country')
            ->orderByDesc('total')
            ->get();

        return view('dashboard.inbox', [
            'chat' => $chat->load(['visitor', 'client', 'visitorSession']),
            'messages' => $chat->messages()->with('sender')->orderBy('created_at', 'desc')->orderBy('id', 'desc')->limit(50)->get()->sortBy('id')->values(),
            'participants' => $participants,
            'files' => $files,
            'pageVisits' => $pageVisits,
            'hasMoreMessages' => $chat->messages()->count() > 50,
            'activeVisitors' => $activeVisitors,
            'recentChats' => $recentChats,
            'clients' => $clients,
            'countries' => $countries,
        ]);
    }

    public function initiateFromSession(VisitorSession $session)
    {
        $user = Auth::user();

        // Verify agent has access to this client
        if (!$user->clients()->where('clients.id', $session->client_id)->exists()) {
            abort(403);
        }

        // Check if chat already exists for this session
        $chat = Chat::where('visitor_session_id', $session->id)->first();

        if (!$chat) {
            // Create new chat
            $chat = Chat::create([
                'visitor_id' => $session->visitor_id,
                'client_id' => $session->client_id,
                'visitor_session_id' => $session->id,
                'status' => 'active',
            ]);

            // Add current user as participant
            $chat->participants()->attach($user->id);
        }

        // Redirect to inbox with this chat
        return redirect()->route('inbox.chat', $chat);
    }

    /**
     * Mark visitor messages as read
     */
    public function markAsRead(Chat $chat)
    {
        $user = Auth::user();

        // Verify agent has access
        if (!$user->clients()->where('clients.id', $chat->client_id)->exists()) {
            abort(403);
        }

        // Mark all visitor messages as read
        $unreadMessages = $chat->messages()
            ->where('sender_type', 'visitor')
            ->where('is_read', false)
            ->get();

        if ($unreadMessages->count() > 0) {
            $messageIds = $unreadMessages->pluck('id')->toArray();
            
            \App\Models\Message::whereIn('id', $messageIds)->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            // Broadcast read receipt to visitor
            event(new \App\Events\MessagesRead($chat, $messageIds, 'agent'));
        }

        return response()->json(['success' => true]);
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

        return redirect()->route('inbox.chat', $chat);
    }
}
