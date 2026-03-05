@extends('layouts.dashboard')

@section('title', 'Clients')

@section('content')
<div class="flex-1 flex flex-col bg-[#0D0D0D] overflow-auto">
    <!-- Header -->
    <div class="bg-[#1A1A1A] border-b border-[#2A2A2A] px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-white">Clients</h1>
                <p class="text-sm text-gray-500">Manage your websites and brands</p>
            </div>
            <a href="{{ route('clients.create') }}" class="flex items-center space-x-2 bg-gradient-to-r from-[#D4AF37] to-[#B8860B] text-black font-medium py-2 px-4 rounded-lg hover:opacity-90 transition-opacity text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add Client</span>
            </a>
        </div>
    </div>

    <div class="p-6">
        @if($clients->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($clients as $client)
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 hover:border-[#D4AF37]/30 transition-all">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-white">{{ $client->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $client->domain ?? 'No domain' }}</p>
                    </div>
                    <span class="px-2 py-0.5 rounded text-xs font-medium {{ $client->is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-500/10 text-gray-500' }}">
                        {{ $client->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="mb-4">
                    <div class="text-xs text-gray-500 mb-1">Widget Key</div>
                    <code class="text-xs bg-[#252525] text-[#D4AF37] px-2 py-1 rounded block truncate">{{ $client->widget_key }}</code>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('clients.show', $client) }}" class="flex-1 text-center bg-[#252525] hover:bg-[#333] px-3 py-2 rounded-lg text-sm text-gray-300 transition-colors">View</a>
                    <a href="{{ route('clients.edit', $client) }}" class="flex-1 text-center bg-[#D4AF37]/10 hover:bg-[#D4AF37]/20 px-3 py-2 rounded-lg text-sm text-[#D4AF37] transition-colors">Edit</a>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="flex flex-col items-center justify-center h-64">
            <div class="w-16 h-16 rounded-full bg-[#1A1A1A] flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                </svg>
            </div>
            <h3 class="text-white font-semibold mb-1">No clients yet</h3>
            <p class="text-gray-500 text-sm mb-4">Add your first client to get started</p>
            <a href="{{ route('clients.create') }}" class="text-[#D4AF37] hover:underline text-sm">+ Create client</a>
        </div>
        @endif
    </div>
</div>
@endsection
