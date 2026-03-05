@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    <div class="bg-[#111] border border-[#222] rounded-lg p-5">
        <div class="text-2xl font-bold text-[#fe9e00]">{{ $stats['users'] ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-1">Total Users</div>
    </div>
    <div class="bg-[#111] border border-[#222] rounded-lg p-5">
        <div class="text-2xl font-bold text-[#fe9e00]">{{ $stats['clients'] ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-1">Active Clients</div>
    </div>
    <div class="bg-[#111] border border-[#222] rounded-lg p-5">
        <div class="text-2xl font-bold text-[#fe9e00]">{{ $stats['chats'] ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-1">Total Chats</div>
    </div>
    <div class="bg-[#111] border border-[#222] rounded-lg p-5">
        <div class="text-2xl font-bold text-[#fe9e00]">{{ $stats['active_chats'] ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-1">Active Chats</div>
    </div>
    <div class="bg-[#111] border border-[#222] rounded-lg p-5">
        <div class="text-2xl font-bold text-[#fe9e00]" id="online-visitors-count">{{ $stats['online'] ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-1">Online Visitors</div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for Reverb/Pusher to initialize (handled in layout)
        const checkReverb = setInterval(() => {
            if (window.reverbClient) {
                clearInterval(checkReverb);
                subscribeToMonitoring();
            }
        }, 500);

        function subscribeToMonitoring() {
            console.log('Subscribing to monitoring channel for visitor stats...');
            const channel = window.reverbClient.subscribe('monitoring');
            
            // New Visitor Joined
            channel.bind('visitor.joined', function(data) {
                console.log('Visitor joined:', data);
                updateVisitorCount(1);
            });
            
            // Visitor Status Changed (Online/Offline)
            channel.bind('status.changed', function(data) {
                console.log('Visitor status changed:', data);
                // If coming online, +1. If going offline, -1.
                // Note: accurate count relies on initial state being correct.
                if (data.is_online) {
                    updateVisitorCount(1);
                } else {
                    updateVisitorCount(-1);
                }
            });
        }
        
        function updateVisitorCount(change) {
            const el = document.getElementById('online-visitors-count');
            if (el) {
                let count = parseInt(el.textContent) || 0;
                count += change;
                if (count < 0) count = 0;
                el.textContent = count;
                
                // Flash effect
                el.style.color = '#fff';
                setTimeout(() => el.style.color = '#fe9e00', 300);
            }
        }
    });
</script>

</div>

<!-- Quick Actions -->
<div class="mb-8">
    <h3 class="font-semibold mb-4 text-gray-400 uppercase text-xs tracking-wider">Quick Actions</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <a href="{{ route('admin.users.index') }}" class="bg-[#111] border border-[#222] p-4 rounded-lg hover:border-[#fe9e00] transition-all hover:-translate-y-1 group">
            <div class="text-[#fe9e00] mb-3 group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </div>
            <div class="text-sm font-medium">Users</div>
        </a>
        <a href="{{ route('admin.clients.index') }}" class="bg-[#111] border border-[#222] p-4 rounded-lg hover:border-[#fe9e00] transition-all hover:-translate-y-1 group">
             <div class="text-[#fe9e00] mb-3 group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            </div>
            <div class="text-sm font-medium">Clients</div>
        </a>
         <a href="{{ route('admin.visitors.index') }}" class="bg-[#111] border border-[#222] p-4 rounded-lg hover:border-[#fe9e00] transition-all hover:-translate-y-1 group">
             <div class="text-[#fe9e00] mb-3 group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
            </div>
            <div class="text-sm font-medium">Visitors</div>
        </a>
         <a href="{{ route('admin.auto-greetings.index') }}" class="bg-[#111] border border-[#222] p-4 rounded-lg hover:border-[#fe9e00] transition-all hover:-translate-y-1 group">
             <div class="text-[#fe9e00] mb-3 group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
            </div>
            <div class="text-sm font-medium">Auto Greetings</div>
        </a>
        <a href="{{ route('admin.roles.index') }}" class="bg-[#111] border border-[#222] p-4 rounded-lg hover:border-[#fe9e00] transition-all hover:-translate-y-1 group">
             <div class="text-[#fe9e00] mb-3 group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            </div>
            <div class="text-sm font-medium">Roles</div>
        </a>
        <a href="{{ route('admin.permissions.index') }}" class="bg-[#111] border border-[#222] p-4 rounded-lg hover:border-[#fe9e00] transition-all hover:-translate-y-1 group">
             <div class="text-[#fe9e00] mb-3 group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div class="text-sm font-medium">Permissions</div>
        </a>
    </div>
</div>

<div class="grid grid-cols-2 gap-6">
    <!-- Recent Users -->
    <div class="bg-[#111] border border-[#222] rounded-lg">
        <div class="px-5 py-4 border-b border-[#222] flex items-center justify-between">
            <h3 class="font-semibold">Recent Users</h3>
            <a href="{{ route('admin.users.index') }}" class="text-xs text-[#fe9e00] hover:underline">View all</a>
        </div>
        <div class="divide-y divide-[#222]">
            @forelse($recentUsers ?? [] as $user)
            <div class="px-5 py-3 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded bg-[#222] flex items-center justify-center text-xs font-bold text-[#fe9e00]">
                        {{ substr($user->name, 0, 2) }}
                    </div>
                    <div class="ml-3">
                        <div class="text-sm font-medium">{{ $user->name }}</div>
                        <div class="text-xs text-gray-500">{{ $user->email }}</div>
                    </div>
                </div>
                <span class="text-xs text-gray-500">{{ $user->created_at->diffForHumans() }}</span>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-gray-500 text-sm">No users yet</div>
            @endforelse
        </div>
    </div>

    <!-- Recent Clients -->
    <div class="bg-[#111] border border-[#222] rounded-lg">
        <div class="px-5 py-4 border-b border-[#222] flex items-center justify-between">
            <h3 class="font-semibold">Recent Clients</h3>
            <a href="{{ route('admin.clients.index') }}" class="text-xs text-[#fe9e00] hover:underline">View all</a>
        </div>
        <div class="divide-y divide-[#222]">
            @forelse($recentClients ?? [] as $client)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium">{{ $client->name }}</div>
                    <div class="text-xs text-gray-500">{{ $client->domain ?? 'No domain' }}</div>
                </div>
                <span class="px-2 py-0.5 rounded text-xs {{ $client->is_active ? 'bg-green-500/10 text-green-400' : 'bg-gray-500/10 text-gray-500' }}">
                    {{ $client->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-gray-500 text-sm">No clients yet</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
