<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="reverb-app-key" content="{{ config('broadcasting.connections.reverb.key') }}">
    <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host') }}">
    <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.options.port') }}">
    <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.options.scheme') }}">
    <title>{{ isset($chat) && $chat ? "Chat #{$chat->id} - " . ($chat->visitor->name ?? 'Anonymous') : 'Live Chat' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .messages-container { scroll-behavior: smooth; }
        .message-bubble { word-break: break-word; }
    </style>
</head>
<body class="bg-black text-white h-screen flex flex-col">
    <!-- Top Header -->
    <header class="bg-[#111] border-b border-[#222] px-4 py-3 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 text-gray-400 hover:text-white mr-4 group" title="Back to Admin Dashboard">
                <svg class="w-6 h-6 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <img src="{{ asset('logo.svg') }}" alt="{{ config('app.name') }}" class="h-8 hidden md:block">
            </a>
            @if(isset($chat) && $chat)
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[#fe9e00]/20 rounded-full flex items-center justify-center">
                    <span class="text-[#fe9e00] font-semibold text-sm">{{ strtoupper(substr($chat->visitor->name ?? 'A', 0, 2)) }}</span>
                </div>
                <div>
                    <h1 class="font-semibold">{{ $chat->visitor->name ?? 'Anonymous Visitor' }}</h1>
                    <p class="text-xs text-gray-500" id="visitor-status">
                        {{ $chat->client->name }} • 
                        @if($chat->visitorSession?->is_online)
                            <span class="text-green-400">Online</span>
                        @else
                            <span>Last seen {{ $chat->visitorSession?->last_activity_at?->diffForHumans() ?? 'Unknown' }}</span>
                        @endif
                    </p>
                </div>
            </div>
            @else
            <div class="flex items-center gap-3">
                 <div class="w-10 h-10 bg-[#fe9e00]/20 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#fe9e00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <div>
                    <h1 class="font-semibold">Live Chat</h1>
                    <p class="text-xs text-gray-500">Select a conversation</p>
                </div>
            </div>
            @endif
        </div>
        <div class="flex items-center gap-3">
            @if(isset($chat) && $chat)
            <span class="px-3 py-1 text-xs rounded-full {{ $chat->status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400' }}">
                {{ ucfirst($chat->status) }}
            </span>
            @endif
            
            <!-- User Menu -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-[#222] transition-colors">
                    <div class="w-7 h-7 rounded-full bg-[#fe9e00]/20 flex items-center justify-center">
                        <span class="text-[#fe9e00] font-semibold text-xs">{{ strtoupper(substr(Auth::user()->active_pseudo_name ?? Auth::user()->name, 0, 2)) }}</span>
                    </div>
                    <span class="text-sm hidden sm:block">{{ Auth::user()->active_pseudo_name ?? Auth::user()->name }}</span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                
                <div x-show="open" @click.away="open = false" x-transition 
                     class="absolute right-0 mt-2 w-48 bg-[#1a1a1a] border border-[#333] rounded-lg shadow-xl py-1 z-50">
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-[#222] hover:text-[#fe9e00]">
                        Profile Settings
                    </a>
                    <div class="border-t border-[#333] my-1"></div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-[#222] hover:text-red-400">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Alpine.js for dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>


    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Left Sidebar -->
        <div class="w-80 border-r border-[#222] bg-[#111] flex flex-col shrink-0">
            <!-- Search & Filters -->
            <div class="p-3 border-b border-[#222]">
                <form action="{{ url()->current() }}" method="GET" id="search-form" class="space-y-2">
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, phone..." 
                            class="w-full bg-[#222] border border-[#333] rounded-lg pl-8 pr-3 py-1.5 text-sm text-white placeholder-gray-600 focus:border-[#fe9e00] focus:outline-none focus:ring-1 focus:ring-[#fe9e00]">
                        <svg class="w-4 h-4 text-gray-500 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <div class="flex gap-2">
                         <select name="client_id" onchange="this.form.submit()" class="flex-1 min-w-0 bg-[#222] border border-[#333] rounded-lg px-2 py-1.5 text-xs text-gray-300 focus:border-[#fe9e00] focus:outline-none">
                             <option value="">All Clients</option>
                             @foreach($clients as $client)
                                 <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                     {{ \Str::limit($client->name, 12) }} ({{ $client->chats_count }})
                                 </option>
                             @endforeach
                         </select>
                         <select name="country" onchange="this.form.submit()" class="w-24 bg-[#222] border border-[#333] rounded-lg px-2 py-1.5 text-xs text-gray-300 focus:border-[#fe9e00] focus:outline-none">
                             <option value="">Country</option>
                             @foreach($countries as $country)
                                 <option value="{{ $country->country_code }}" {{ request('country') == $country->country_code ? 'selected' : '' }}>
                                     {{ $country->country_code }} ({{ $country->total }})
                                 </option>
                             @endforeach
                         </select>
                    </div>
                </form>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-[#222]">
                <button onclick="switchTab('active')" id="tab-active" class="flex-1 px-4 py-3 text-sm font-medium border-b-2 border-[#fe9e00] text-[#fe9e00]">
                    Active Visitors
                </button>
                <button onclick="switchTab('all')" id="tab-all" class="flex-1 px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white">
                    All Chats
                </button>
            </div>

            <!-- Active Visitors List -->
            <div id="list-active" class="flex-1 overflow-y-auto">
                @forelse($activeVisitors as $session)
                    @php 
                        $visitorChat = $session->chats->first();
                        $isCurrentChat = isset($chat) && $visitorChat && $visitorChat->id === $chat->id;
                    @endphp
                    @if($visitorChat)
                        {{-- Visitor has a chat - link to it --}}
                        <a href="{{ route('inbox.chat', $visitorChat) }}" 
                           class="block p-4 border-b border-[#222] hover:bg-[#1a1a1a] {{ $isCurrentChat ? 'bg-[#1a1a1a]' : '' }}">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-[#fe9e00]/20 rounded-full flex items-center justify-center shrink-0">
                                    <span class="text-[#fe9e00] font-semibold text-sm">{{ strtoupper(substr($session->visitor->name ?? 'A', 0, 2)) }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium truncate">{{ $session->visitor->name ?? 'Anonymous' }}</h4>
                                        <span class="w-2 h-2 bg-green-500 rounded-full shrink-0"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 truncate">{{ $session->client->name }}</p>
                                    <p class="text-sm text-gray-400 truncate mt-1">Active now</p>
                                </div>
                            </div>
                        </a>
                    @else
                        {{-- Visitor online but no chat yet - clickable to initiate chat --}}
                        <a href="{{ route('inbox.initiate', $session) }}" 
                           class="block p-4 border-b border-[#222] hover:bg-[#1a1a1a]">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-[#fe9e00]/20 rounded-full flex items-center justify-center shrink-0">
                                    <span class="text-[#fe9e00] font-semibold text-sm">{{ strtoupper(substr($session->visitor->name ?? 'A', 0, 2)) }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium truncate">{{ $session->visitor->name ?? 'Anonymous' }}</h4>
                                        <span class="w-2 h-2 bg-green-500 rounded-full shrink-0"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 truncate">{{ $session->client->name }}</p>
                                    <p class="text-xs text-gray-400 truncate mt-1">Click to start chat</p>
                                </div>
                            </div>
                        </a>
                    @endif
                @empty
                <div class="p-4 text-center text-gray-500">
                    <p class="text-sm">No active visitors</p>
                </div>
                @endforelse
            </div>

            <!-- All Chats List -->
            <div id="list-all" class="flex-1 overflow-y-auto hidden">
                @forelse($recentChats as $recentChat)
                <a href="{{ route('inbox.chat', $recentChat) }}" 
                   class="block p-4 border-b border-[#222] hover:bg-[#1a1a1a] {{ isset($chat) && $recentChat->id === $chat->id ? 'bg-[#1a1a1a]' : '' }}">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-[#fe9e00]/20 rounded-full flex items-center justify-center shrink-0">
                            <span class="text-[#fe9e00] font-semibold text-sm">{{ strtoupper(substr($recentChat->visitor->name ?? 'A', 0, 2)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium truncate">{{ $recentChat->visitor->name ?? 'Anonymous' }}</h4>
                                @if($recentChat->visitorSession?->is_online)
                                <span class="w-2 h-2 bg-green-500 rounded-full shrink-0"></span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 truncate">{{ $recentChat->client->name }}</p>
                            <p class="text-sm text-gray-400 truncate mt-1">
                                {{ $recentChat->last_message_at?->diffForHumans() ?? 'No messages' }}
                            </p>
                        </div>
                    </div>
                </a>
                @empty
                <div class="p-4 text-center text-gray-500">
                    <p class="text-sm">No chats yet</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Chat Area (Center) -->
        <div class="flex-1 flex flex-col">
            @if(isset($chat) && $chat)
            <!-- Messages -->
            <div class="flex-1 overflow-y-auto p-4 messages-container" id="messages-container">
                @foreach($messages as $message)
                <div class="mb-4 flex {{ $message->sender_type === 'visitor' ? 'justify-start' : 'justify-end' }}">
                    <div class="max-w-[70%]">
                        <div class="message-bubble px-4 py-2 rounded-2xl {{ $message->sender_type === 'visitor' ? 'bg-[#222] text-white' : 'bg-[#fe9e00] text-black' }}">
                            @if($message->message_type === 'file')
                                @if($message->file_type && str_starts_with($message->file_type, 'image/'))
                                    <a href="{{ route('dashboard.chat.file.download', [$chat, $message->id]) }}" target="_blank">
                                        <img src="{{ route('dashboard.chat.file.download', [$chat, $message->id]) }}" 
                                             alt="{{ $message->file_name }}" 
                                             class="max-w-full rounded cursor-pointer max-h-64">
                                    </a>
                                    <div class="text-xs mt-1">{{ $message->file_name }}</div>
                                @else
                                    <a href="{{ route('dashboard.chat.file.download', [$chat, $message->id]) }}" 
                                       download="{{ $message->file_name }}"
                                       class="flex items-center gap-2 hover:underline">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                        </svg>
                                        <span>{{ $message->file_name }}</span>
                                    </a>
                                @endif
                            @else
                                {{ $message->message }}
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-1 {{ $message->sender_type === 'visitor' ? 'text-left' : 'text-right' }}">
                            {{ $message->created_at->format('H:i') }}
                            @if($message->sender_type === 'agent')
                                <span id="msg-status-{{ $message->id }}" class="ml-1 inline-flex {{ $message->is_read ? 'text-blue-400' : 'text-gray-400' }}" title="{{ $message->is_read ? 'Read' : 'Sent' }}">
                                    @if($message->is_read)
                                        {{-- Double tick for read --}}
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" transform="translate(3, 0)"/>
                                        </svg>
                                    @else
                                        {{-- Single tick for sent --}}
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @endif
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
                @endforeach
                <div id="typing-indicator" class="hidden mb-4">
                    <div class="bg-[#222] text-gray-400 px-4 py-2 rounded-2xl inline-block">
                        <span class="animate-pulse">Typing...</span>
                    </div>
                </div>
            </div>

            <!-- Message Input -->
            <div class="border-t border-[#222] p-4 bg-[#111] relative">
                <!-- Canned Response Dropdown -->
                <div id="canned-dropdown" class="hidden absolute bottom-full left-4 right-4 mb-2 bg-[#1a1a1a] border border-[#333] rounded-lg shadow-lg max-h-64 overflow-y-auto z-50">
                    <div id="canned-list" class="divide-y divide-[#222]">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>

                <form id="message-form" class="flex gap-3">
                    <!-- Nickname Selector -->
                    @php
                        $pseudoNames = Auth::user()->pseudo_names ?? [];
                        $currentName = Auth::user()->active_pseudo_name ?? Auth::user()->name;
                    @endphp
                    @if(count($pseudoNames) > 0)
                    <div class="relative shrink-0" x-data="{ open: false }">
                        <button type="button" @click="open = !open" 
                            class="flex items-center gap-2 h-full px-4 bg-[#222] border-[1.5px] border-[#333] hover:border-[#fe9e00] rounded-full text-sm transition-all text-[#fe9e00] font-medium group">
                            <svg class="w-4 h-4 text-gray-500 group-hover:text-[#fe9e00] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span id="selected-nickname">{{ $currentName }}</span>
                            <svg class="w-3 h-3 text-gray-500 group-hover:text-[#fe9e00] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" x-transition 
                             class="absolute bottom-full left-0 mb-2 w-48 bg-[#1a1a1a] border border-[#333] rounded-lg shadow-xl py-1 z-50">
                            <div class="px-4 py-2 text-[10px] text-gray-500 font-bold uppercase tracking-wider">Switch Profile</div>
                            @foreach($pseudoNames as $name)
                            <button type="button" onclick="selectNickname('{{ $name }}')" 
                                    class="block w-full text-left px-4 py-2 text-sm hover:bg-[#222] text-gray-300 hover:text-[#fe9e00] flex items-center justify-between group">
                                <span>{{ $name }}</span>
                                <span class="opacity-0 group-hover:opacity-100 text-[#fe9e00] text-xs transition-opacity">Select</span>
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    <input type="text" id="message-input" placeholder="Type a message... (type / for quick replies)" 
                        class="flex-1 bg-[#222] border border-[#333] rounded-full px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-[#fe9e00]">
                    <button type="button" id="attach-btn" class="p-2 text-gray-400 hover:text-[#fe9e00]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                    </button>
                    <button type="submit" class="bg-[#fe9e00] text-black px-6 py-2 rounded-full font-medium hover:bg-[#e08e00]">
                        Send
                    </button>
                    <input type="file" id="file-input" style="display: none;" accept="image/*,.pdf,.doc,.docx">
                </form>
            </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-gray-500">
                    <div class="w-20 h-20 bg-[#fe9e00]/10 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-10 h-10 text-[#fe9e00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-white mb-2">Select a Conversation</h2>
                    <p class="max-w-md text-center">Refer to the sidebar to choose an active visitor or a recent chat to start messaging.</p>
                </div>
            @endif
        </div>

    @if(isset($chat) && $chat)
        <!-- Visitor Details Panel (Right) -->
        <div class="w-80 border-l border-[#222] bg-[#111] overflow-y-auto shrink-0">
            <!-- Visitor Details -->
            <div class="p-4 border-b border-[#222]">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Contact Details</h3>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-[#222] rounded flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Name</p>
                            <p class="text-sm">{{ $chat->visitor->name ?? 'Anonymous' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-[#222] rounded flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Email</p>
                            <p class="text-sm">{{ $chat->visitor->email ?? 'Not provided' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-[#222] rounded flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Phone</p>
                            <p class="text-sm">{{ $chat->visitor->phone ?? 'Not provided' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Uploaded Files -->
            <div class="p-4 border-b border-[#222]">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Shared Files</h3>
                @if($files->count() > 0)
                <div class="space-y-2">
                    @foreach($files as $file)
                    <div class="flex items-center gap-2 p-2 bg-[#222] rounded">
                        <svg class="w-4 h-4 text-[#fe9e00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm truncate">{{ $file->file_name }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-500">No files shared yet</p>
                @endif
            </div>

            <!-- Page Browsing History (Real-time) -->
            <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Browsing History</h3>
                    <span id="current-page-indicator" class="w-2 h-2 bg-green-500 rounded-full {{ $chat->visitorSession?->is_online ? '' : 'hidden' }}"></span>
                </div>
                <div id="page-visits-container" class="space-y-2">
                    @if($chat->visitorSession?->is_online && $chat->visitorSession?->current_page)
                    <div id="current-page" class="p-2 bg-[#fe9e00]/10 border border-[#fe9e00]/30 rounded">
                        <p class="text-xs text-[#fe9e00] font-medium">Currently viewing</p>
                        <p class="text-sm truncate">{{ $chat->visitorSession->current_page }}</p>
                    </div>
                    @endif
                    @forelse($pageVisits as $visit)
                    <div class="p-2 bg-[#222] rounded">
                        <p class="text-sm truncate">{{ $visit->page_title ?? $visit->page_url }}</p>
                        <p class="text-xs text-gray-500">{{ $visit->created_at->format('H:i:s') }}</p>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500">No pages visited</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
    </div>

    <script>
        // Initialize Pusher/Reverb
        const pusher = new Pusher('{{ config('reverb.apps.apps.0.key') }}', {
            wsHost: '{{ config('broadcasting.connections.reverb.options.host', '127.0.0.1') }}',
            wsPort: {{ config('broadcasting.connections.reverb.options.port', 8080) }},
            wssPort: {{ config('broadcasting.connections.reverb.options.port', 8080) }},
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            cluster: 'mt1',
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }
        });

        // Subscribe to agent private channel for notifications
        const userId = {{ Auth::id() }};
        console.log('Subscribing to private-agent.' + userId);
        const agentChannel = pusher.subscribe('private-agent.' + userId);
        agentChannel.bind('agent.notification', function(data) {
            console.log('Agent notification received:', data);
            // Only show notification if not already on this chat page
            if (!data.url || !window.location.href.includes(data.url)) {
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification(data.title, { body: data.body });
                }
            }
        });

        @if(isset($chat) && $chat)
        const chatId = {{ $chat->id }}; // Must use numeric ID to match broadcast channel
        const sessionId = {{ $chat->visitorSession?->id ?? 'null' }};

        // Subscribe to chat channel (public)
        const chatChannel = pusher.subscribe('chat.' + chatId);
        
        chatChannel.bind('message.sent', function(data) {
            console.log('Received message event:', data);
            // Only show visitor messages (agent messages added optimistically)
            if (data.sender_type === 'visitor') {
                addMessage(data);
                // Play notification sound for visitor messages
                playNotificationSound();
                if (document.visibilityState === 'visible') {
                    markAsRead();
                }
            }
        });

        chatChannel.bind('messages.read', function(data) {
            console.log('Read receipt received:', data);
            if (data.read_by === 'visitor') {
                data.message_ids.forEach(id => {
                    const el = document.getElementById(`msg-status-${id}`);
                    if (el) {
                        el.classList.remove('text-gray-400');
                        el.classList.add('text-blue-400');
                        el.title = 'Read';
                        el.innerHTML = `
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" transform="translate(3, 0)"/>
                            </svg>
                        `;
                    }
                });
            }
        });

        chatChannel.bind('visitor.typing', function(data) {
            showTyping();
        });

        // Subscribe to visitor session status changes
        @if($chat->visitorSession)
        const sessionChannel = pusher.subscribe('visitor-session.{{ $chat->visitorSession->id }}');
        sessionChannel.bind('status.changed', function(data) {
            // Update online/offline indicator in header
            const statusEl = document.getElementById('visitor-status');
            if (statusEl) {
                const clientName = '{{ $chat->client->name }}';
                if (data.is_online) {
                    statusEl.innerHTML = clientName + ' • <span class="text-green-400">Online</span>';
                } else {
                    statusEl.innerHTML = clientName + ' • <span>Last seen just now</span>';
                }
            }
        });
        @endif

        // Subscribe to visitor session for page changes
        if (sessionId) {
            const sessionChannel = pusher.subscribe('visitor-session.' + sessionId);
            sessionChannel.bind('page.changed', function(data) {
                updateCurrentPage(data.page_url, data.page_title);
            });
        }
        @endif

        function addMessage(msg) {
            console.log('Adding message:', msg);
            const container = document.getElementById('messages-container');
            const isVisitor = msg.sender_type === 'visitor';
            const div = document.createElement('div');
            div.className = 'mb-4 flex ' + (isVisitor ? 'justify-start' : 'justify-end');
            
            let messageContent = '';
            if (msg.message_type === 'file') {
                if (msg.file_type && msg.file_type.startsWith('image/')) {
                    messageContent = `
                        <img src="/dashboard/chat/${chatId}/file/${msg.id}/download" 
                             alt="${msg.file_name}" 
                             class="max-w-full rounded cursor-pointer max-h-64"
                             onclick="window.open(this.src, '_blank')">
                        <div class="text-xs mt-1">${msg.file_name}</div>
                    `;
                } else {
                    messageContent = `
                        <a href="/dashboard/chat/${chatId}/file/${msg.id}/download" 
                           download="${msg.file_name}"
                           class="flex items-center gap-2 hover:underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                            ${msg.file_name}
                        </a>
                    `;
                }
            } else {
                messageContent = msg.message || '';
            }
            
            div.innerHTML = `
                <div class="max-w-[70%]">
                    <div class="message-bubble px-4 py-2 rounded-2xl ${isVisitor ? 'bg-[#222] text-white' : 'bg-[#fe9e00] text-black'}">
                        ${messageContent}
                    </div>
                    <p class="text-xs text-gray-500 mt-1 ${isVisitor ? 'text-left' : 'text-right'}">
                        ${new Date(msg.created_at).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}
                        ${!isVisitor ? `
                            <span id="msg-status-${msg.id}" class="ml-1 inline-flex text-gray-400" title="Sent">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                        ` : ''}
                    </p>
                </div>
            `;
            // Append at end (before typing indicator if exists)
            const typingIndicator = document.getElementById('typing-indicator');
            if (typingIndicator) {
                container.insertBefore(div, typingIndicator);
            } else {
                container.appendChild(div);
            }
            container.scrollTop = container.scrollHeight;
        }

        function showTyping() {
            const indicator = document.getElementById('typing-indicator');
            indicator.classList.remove('hidden');
            setTimeout(() => indicator.classList.add('hidden'), 3000);
        }

        // Create audio context on first user interaction
        let audioContext = null;
        function initAudioContext() {
            if (!audioContext) {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
            }
            return audioContext;
        }

        // Initialize on first click/keypress
        document.addEventListener('click', initAudioContext, { once: true });
        document.addEventListener('keypress', initAudioContext, { once: true });

        function playNotificationSound() {
            try {
                const ctx = initAudioContext();
                if (ctx.state === 'suspended') {
                    ctx.resume();
                }
                
                const oscillator = ctx.createOscillator();
                const gainNode = ctx.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(ctx.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                gainNode.gain.value = 0.1;
                
                oscillator.start();
                setTimeout(() => oscillator.stop(), 150);
            } catch (e) {
                console.log('Could not play notification sound:', e);
            }
        }

        function updateCurrentPage(url, title) {
            const container = document.getElementById('page-visits-container');
            let currentDiv = document.getElementById('current-page');
            
            if (!currentDiv) {
                currentDiv = document.createElement('div');
                currentDiv.id = 'current-page';
                currentDiv.className = 'p-2 bg-[#fe9e00]/10 border border-[#fe9e00]/30 rounded';
                container.insertBefore(currentDiv, container.firstChild);
            }
            
            currentDiv.innerHTML = `
                <p class="text-xs text-[#fe9e00] font-medium">Currently viewing</p>
                <p class="text-sm truncate">${title || url}</p>
            `;

            // Add to history
            const historyDiv = document.createElement('div');
            historyDiv.className = 'p-2 bg-[#222] rounded';
            historyDiv.innerHTML = `
                <p class="text-sm truncate">${title || url}</p>
                <p class="text-xs text-gray-500">${new Date().toLocaleTimeString()}</p>
            `;
            currentDiv.insertAdjacentElement('afterend', historyDiv);
        }

        // Send message
        document.getElementById('message-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            if (!message) return;

            fetch('/api/agent/chat/' + chatId + '/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ message: message })
            })
            .then(res => res.json())
            .then(data => {
                input.value = '';
                addMessage(data.message);
            })
            .catch(err => console.error('Send error:', err));
        });
        
        // File upload
        document.getElementById('attach-btn').addEventListener('click', function() {
            document.getElementById('file-input').click();
        });
        
        document.getElementById('file-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('file', file);
            
            fetch('/dashboard/chat/' + chatId + '/file', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.message) {
                    addMessage(data.message);
                }
                e.target.value = ''; // Reset file input
            })
            .catch(err => console.error('File upload error:', err));
        });
        
        function markAsRead() {
            fetch('/dashboard/chat/' + chatId + '/read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
        }

        // Mark read on load and focus
        markAsRead();
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                markAsRead();
            }
        });

        // Scroll to bottom on load
        document.getElementById('messages-container').scrollTop = document.getElementById('messages-container').scrollHeight;

        // Tab switching
        function switchTab(tab) {
            const activeTab = document.getElementById('tab-active');
            const allTab = document.getElementById('tab-all');
            const activeList = document.getElementById('list-active');
            const allList = document.getElementById('list-all');

            if (tab === 'active') {
                activeTab.classList.add('border-[#fe9e00]', 'text-[#fe9e00]');
                activeTab.classList.remove('border-transparent', 'text-gray-400');
                allTab.classList.remove('border-[#fe9e00]', 'text-[#fe9e00]');
                allTab.classList.add('border-transparent', 'text-gray-400');
                activeList.classList.remove('hidden');
                allList.classList.add('hidden');
            } else {
                allTab.classList.add('border-[#fe9e00]', 'text-[#fe9e00]');
                allTab.classList.remove('border-transparent', 'text-gray-400');
                activeTab.classList.remove('border-[#fe9e00]', 'text-[#fe9e00]');
                activeTab.classList.add('border-transparent', 'text-gray-400');
                allList.classList.remove('hidden');
                activeList.classList.add('hidden');
            }
        }

        @if(isset($chat) && $chat)
        // Canned Responses
        let cannedResponses = [];
        let selectedCannedIndex = -1;
        const messageInput = document.getElementById('message-input');
        const cannedDropdown = document.getElementById('canned-dropdown');
        const cannedList = document.getElementById('canned-list');

        messageInput.addEventListener('input', function(e) {
            const value = e.target.value;
            const lastChar = value.slice(-1);
            const words = value.split(' ');
            const lastWord = words[words.length - 1];

            // Check if last word starts with /
            if (lastWord.startsWith('/') && lastWord.length > 1) {
                const query = lastWord.substring(1);
                fetchCannedResponses(query);
            } else if (lastChar === '/' && (value.length === 1 || value.slice(-2, -1) === ' ')) {
                // Just typed / at start or after space
                fetchCannedResponses('');
            } else {
                hideCannedDropdown();
            }
            
            // Send typing indicator (debounced)
            if (!window.typingTimeout) {
                fetch(`/api/agent/chat/{{ $chat->id }}/typing`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }).catch(() => {}); // Ignore errors
            }
            clearTimeout(window.typingTimeout);
            window.typingTimeout = setTimeout(() => window.typingTimeout = null, 2000);
        });

        messageInput.addEventListener('keydown', function(e) {
            if (!cannedDropdown.classList.contains('hidden')) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedCannedIndex = Math.min(selectedCannedIndex + 1, cannedResponses.length - 1);
                    updateCannedSelection();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedCannedIndex = Math.max(selectedCannedIndex - 1, 0);
                    updateCannedSelection();
                } else if (e.key === 'Enter' && selectedCannedIndex >= 0) {
                    e.preventDefault();
                    selectCannedResponse(cannedResponses[selectedCannedIndex]);
                } else if (e.key === 'Escape') {
                    hideCannedDropdown();
                }
            }
        });

        function fetchCannedResponses(query) {
            fetch(`/api/agent/canned-responses/search?q=${encodeURIComponent(query)}&client_id={{ $chat->client_id }}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(res => res.json())
            .then(data => {
                cannedResponses = data;
                if (data.length > 0) {
                    showCannedDropdown(data);
                } else {
                    hideCannedDropdown();
                }
            })
            .catch(err => console.error('Canned response fetch error:', err));
        }

        function showCannedDropdown(responses) {
            selectedCannedIndex = 0;
            cannedList.innerHTML = responses.map((resp, index) => `
                <div class="canned-item p-3 hover:bg-[#222] cursor-pointer ${index === 0 ? 'bg-[#222]' : ''}" data-index="${index}">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[#fe9e00] text-sm font-medium">${resp.shortcut}</span>
                        ${resp.category ? `<span class="text-xs text-gray-500">${resp.category}</span>` : ''}
                    </div>
                    <div class="text-sm text-white font-medium">${resp.title}</div>
                    <div class="text-xs text-gray-400 mt-1 line-clamp-2">${resp.content}</div>
                </div>
            `).join('');

            // Add click handlers
            document.querySelectorAll('.canned-item').forEach((item, index) => {
                item.addEventListener('click', () => selectCannedResponse(responses[index]));
            });

            cannedDropdown.classList.remove('hidden');
        }

        function hideCannedDropdown() {
            cannedDropdown.classList.add('hidden');
            selectedCannedIndex = -1;
        }

        function updateCannedSelection() {
            document.querySelectorAll('.canned-item').forEach((item, index) => {
                if (index === selectedCannedIndex) {
                    item.classList.add('bg-[#222]');
                } else {
                    item.classList.remove('bg-[#222]');
                }
            });
        }

        function selectCannedResponse(response) {
            const input = messageInput;
            const value = input.value;
            const words = value.split(' ');
            
            // Remove the /shortcut part
            words.pop();
            const newValue = words.length > 0 ? words.join(' ') + ' ' + response.content : response.content;
            
            input.value = newValue;
            input.focus();
            hideCannedDropdown();

            // Track usage
            fetch(`/api/agent/canned-responses/${response.id}/use`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
        }

        // Select nickname for this chat
        function selectNickname(nickname) {
            fetch(`/api/agent/chat/${chatId}/nickname`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ nickname: nickname })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('selected-nickname').textContent = nickname;
                }
            })
            .catch(err => console.error('Nickname update error:', err));
        }
        @endif
    </script>
</body>
</html>
