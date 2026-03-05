@extends('layouts.dashboard')

@section('title', 'Live Visitors')

@section('content')
<div class="flex-1 flex flex-col bg-[#0D0D0D] overflow-hidden">
    <!-- Header -->
    <div class="bg-[#1A1A1A] border-b border-[#2A2A2A] px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-white">Live Visitors</h1>
                <p class="text-sm text-gray-500">Monitor and engage with visitors in real-time</p>
            </div>
            <div class="flex items-center space-x-2 bg-emerald-500/10 px-4 py-2 rounded-lg">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <span class="font-bold text-emerald-400">{{ $onlineVisitors->count() }}</span>
                <span class="text-sm text-emerald-400/70">online</span>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-auto p-6">
        @if($onlineVisitors->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
            @foreach ($onlineVisitors as $session)
            <div class="bg-[#1A1A1A] rounded-xl border border-[#2A2A2A] p-4 hover:border-[#D4AF37]/30 transition-all group">
                <!-- Header -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#D4AF37] to-[#B8860B] flex items-center justify-center text-black text-sm font-bold">
                                {{ substr($session->visitor->name ?? 'V', 0, 1) }}
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 border-2 border-[#1A1A1A] rounded-full"></span>
                        </div>
                        <div>
                            <div class="font-medium text-white text-sm">{{ $session->visitor->name ?? 'Anonymous' }}</div>
                            <div class="text-xs text-gray-500">{{ $session->client->name }}</div>
                        </div>
                    </div>
                    <span class="text-[10px] text-gray-500">{{ $session->started_at->diffForHumans(null, true) }}</span>
                </div>

                <!-- Info -->
                <div class="space-y-2 mb-4 text-sm">
                    <div class="flex items-center space-x-2 text-gray-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <span>{{ $session->visitor->city ?? 'Unknown' }}, {{ $session->visitor->country ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center space-x-2 text-gray-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/>
                        </svg>
                        <a href="{{ $session->current_page }}" target="_blank" class="text-[#D4AF37] hover:underline truncate">
                            {{ Str::limit($session->current_page, 35) }}
                        </a>
                    </div>
                </div>

                <!-- Action -->
                <form action="{{ route('dashboard.chat.initiate') }}" method="POST">
                    @csrf
                    <input type="hidden" name="session_id" value="{{ $session->id }}">
                    <button type="submit" class="w-full flex items-center justify-center space-x-2 bg-gradient-to-r from-[#D4AF37] to-[#B8860B] text-black font-medium py-2 px-4 rounded-lg hover:opacity-90 transition-opacity text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <span>Start Chat</span>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
        @else
        <!-- Empty -->
        <div class="flex flex-col items-center justify-center h-80">
            <div class="w-16 h-16 rounded-full bg-[#1A1A1A] flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
            <h3 class="text-white font-semibold mb-1">No visitors online</h3>
            <p class="text-gray-500 text-sm">Visitors will appear here when they browse your sites</p>
        </div>
        @endif
    </div>
</div>
@endsection
