<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Client;
use App\Models\User;
use App\Models\VisitorSession;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'clients' => Client::where('is_active', true)->count(),
            'chats' => Chat::count(),
            'active_chats' => Chat::where('status', 'active')->count(),
            'online' => VisitorSession::where('is_online', true)->count(),
        ];

        $recentUsers = User::with('roles')->latest()->take(5)->get();
        $recentClients = Client::latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentClients'));
    }
}
