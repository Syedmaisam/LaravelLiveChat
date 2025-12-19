@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-4 gap-4 mb-8">
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
        <div class="text-2xl font-bold text-[#fe9e00]">{{ $stats['online'] ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-1">Online Visitors</div>
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
