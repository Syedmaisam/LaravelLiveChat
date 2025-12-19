@extends('layouts.admin')

@section('title', 'Inbox')

@section('content')
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-[#111] border border-[#222] rounded-lg p-5">
        <div class="text-2xl font-bold text-[#fe9e00]">{{ $waitingChats->count() }}</div>
        <div class="text-sm text-gray-500 mt-1">Waiting Chats</div>
    </div>
    <div class="bg-[#111] border border-[#222] rounded-lg p-5">
        <div class="text-2xl font-bold text-[#fe9e00]">{{ $activeChats->count() }}</div>
        <div class="text-sm text-gray-500 mt-1">Active Chats</div>
    </div>
    <div class="bg-[#111] border border-[#222] rounded-lg p-5">
        <div class="text-2xl font-bold text-[#fe9e00]">{{ $onlineVisitors->count() }}</div>
        <div class="text-sm text-gray-500 mt-1">Online Visitors</div>
    </div>
    <div class="bg-[#111] border border-[#222] rounded-lg p-5">
        <div class="text-2xl font-bold text-white">{{ $totalChats ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-1">Total Chats Today</div>
    </div>
</div>

<div class="grid grid-cols-3 gap-6">
    <!-- Waiting Chats -->
    <div class="bg-[#111] border border-[#222] rounded-lg">
        <div class="px-5 py-4 border-b border-[#222] flex items-center justify-between">
            <h3 class="font-semibold">Waiting</h3>
            <span class="bg-red-500/20 text-red-400 text-xs font-bold px-2 py-0.5 rounded">{{ $waitingChats->count() }}</span>
        </div>
        <div class="divide-y divide-[#222] max-h-80 overflow-y-auto">
            @forelse($waitingChats as $chat)
            <a href="{{ route('dashboard.chat', $chat) }}" class="block px-5 py-3 hover:bg-[#1a1a1a]">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-medium text-sm">{{ $chat->visitor->name ?? 'New Visitor' }}</div>
                        <div class="text-xs text-gray-500">{{ $chat->client->name }}</div>
                    </div>
                    <span class="text-xs text-gray-500">{{ $chat->started_at->diffForHumans(null, true) }}</span>
                </div>
            </a>
            @empty
            <div class="px-5 py-8 text-center text-gray-500 text-sm">No waiting chats</div>
            @endforelse
        </div>
    </div>

    <!-- Active Chats -->
    <div class="bg-[#111] border border-[#222] rounded-lg">
        <div class="px-5 py-4 border-b border-[#222] flex items-center justify-between">
            <h3 class="font-semibold">Active</h3>
            <span class="text-xs text-gray-500">{{ $activeChats->count() }}</span>
        </div>
        <div class="divide-y divide-[#222] max-h-80 overflow-y-auto">
            @forelse($activeChats as $chat)
            <a href="{{ route('dashboard.chat', $chat) }}" class="block px-5 py-3 hover:bg-[#1a1a1a]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded bg-[#222] flex items-center justify-center text-xs font-bold text-[#fe9e00] mr-3">
                            {{ substr($chat->visitor->name ?? 'V', 0, 1) }}
                        </div>
                        <div>
                            <div class="font-medium text-sm">{{ $chat->visitor->name ?? 'Visitor' }}</div>
                            <div class="text-xs text-gray-500">{{ $chat->client->name }}</div>
                        </div>
                    </div>
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                </div>
            </a>
            @empty
            <div class="px-5 py-8 text-center text-gray-500 text-sm">No active chats</div>
            @endforelse
        </div>
    </div>

    <!-- Online Visitors -->
    <div class="bg-[#111] border border-[#222] rounded-lg">
        <div class="px-5 py-4 border-b border-[#222] flex items-center justify-between">
            <h3 class="font-semibold">Online Visitors</h3>
            <a href="{{ route('dashboard.monitoring') }}" class="text-xs text-[#fe9e00] hover:underline">View all</a>
        </div>
        <div class="divide-y divide-[#222] max-h-80 overflow-y-auto">
            @forelse($onlineVisitors->take(5) as $session)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <div class="font-medium text-sm">{{ $session->visitor->name ?? 'Anonymous' }}</div>
                    <div class="text-xs text-gray-500">{{ $session->client->name }}</div>
                </div>
                <form action="{{ route('dashboard.chat.initiate') }}" method="POST">
                    @csrf
                    <input type="hidden" name="session_id" value="{{ $session->id }}">
                    <button type="submit" class="text-xs text-[#fe9e00] hover:underline">Chat</button>
                </form>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-gray-500 text-sm">No visitors online</div>
            @endforelse
        </div>
    </div>
</div>

@if(isset($currentChat))
<!-- Current Chat Modal/Section -->
<div class="mt-6 bg-[#111] border border-[#222] rounded-lg">
    <div class="px-5 py-4 border-b border-[#222] flex items-center justify-between">
        <div class="flex items-center">
            <div class="w-10 h-10 rounded bg-[#222] flex items-center justify-center text-sm font-bold text-[#fe9e00] mr-3">
                {{ substr($currentChat->visitor->name ?? 'V', 0, 1) }}
            </div>
            <div>
                <h3 class="font-semibold">{{ $currentChat->visitor->name ?? 'Visitor' }}</h3>
                <div class="text-xs text-gray-500">{{ $currentChat->client->name }}</div>
            </div>
        </div>
        @if ($currentChat->status !== 'closed')
        <form action="{{ route('dashboard.chat.close', $currentChat) }}" method="POST">
            @csrf
            <button type="submit" class="text-red-400 hover:underline text-sm">Close Chat</button>
        </form>
        @endif
    </div>
    
    <div class="h-96 overflow-y-auto p-5 space-y-3" id="messages-container">
        @foreach ($messages as $message)
        <div class="flex {{ $message->sender_type === 'agent' ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[60%] px-4 py-2 rounded-lg {{ $message->sender_type === 'agent' ? 'bg-[#fe9e00] text-black' : 'bg-[#222] text-white' }}">
                <p class="text-sm">{{ $message->message }}</p>
                <div class="text-[10px] {{ $message->sender_type === 'agent' ? 'text-black/60' : 'text-gray-500' }} mt-1">{{ $message->created_at->format('H:i') }}</div>
            </div>
        </div>
        @endforeach
    </div>
    
    <div class="p-4 border-t border-[#222]">
        <form action="{{ route('dashboard.chat.message', $currentChat) }}" method="POST" class="flex space-x-3">
            @csrf
            <input type="text" name="message" placeholder="Type a message..." required
                class="flex-1 bg-black border border-[#333] rounded px-4 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
            <button type="submit" class="bg-[#fe9e00] text-black px-4 py-2 rounded font-medium hover:bg-[#e08e00]">Send</button>
        </form>
    </div>
</div>
@endif
@endsection
