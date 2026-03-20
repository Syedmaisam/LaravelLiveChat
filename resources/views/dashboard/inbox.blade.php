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
        @keyframes slideIn { from { transform: translateX(110%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(110%); opacity: 0; } }
        .toast-slide-in { animation: slideIn 0.35s ease-out forwards; }
        .toast-slide-out { animation: slideOut 0.3s ease-in forwards; }
    </style>
</head>
<body class="bg-black text-white h-screen flex flex-col">
    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-[100] flex flex-col gap-3 pointer-events-none" style="max-width: 380px;"></div>

    <!-- Top Header -->
    <header class="bg-[#111] border-b border-[#222] px-4 py-3 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 text-gray-400 hover:text-white mr-4 group" title="Back to Admin Dashboard">
                <svg class="w-6 h-6 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <img src="{{ asset('visiontechlogow.webp') }}" style="height: 50px;" alt="{{ config('app.name') }}" class="h-8 hidden md:block">
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
            <span class="px-3 py-1 text-xs rounded-full {{ $chat->status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400' }}" id="chat-status-badge">
                {{ ucfirst($chat->status) }}
            </span>
            @if($chat->status === 'active')
            <button onclick="endChat()" id="end-chat-btn" class="px-3 py-1 text-xs rounded-full bg-red-500/20 text-red-400 hover:bg-red-500/30 transition-colors font-medium">
                End Chat
            </button>
            @endif
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


    <!-- Connection Banner -->
    <div id="connection-banner" class="hidden bg-yellow-500/20 border-b border-yellow-500/30 px-4 py-2 text-center text-yellow-400 text-sm shrink-0">
        <span class="animate-pulse">Reconnecting to server...</span>
    </div>

    <!-- Ringing Banner for new waiting chats -->
    <div id="ringing-banner" class="hidden bg-green-500/20 border-b border-green-500/40 px-4 py-3 text-center shrink-0 cursor-pointer hover:bg-green-500/30 transition-colors"
         onclick="document.querySelector('[data-ringing-chat]')?.click(); stopRinging();">
        <div class="flex items-center justify-center gap-3">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </span>
            <span class="ringing-text text-green-400 font-medium text-sm">1 new chat waiting — click to answer</span>
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </span>
        </div>
    </div>

    @if(!Auth::user()->active_pseudo_name)
    <!-- Pseudo Name Setup Modal -->
    <div id="pseudo-name-setup-modal" class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4">
        <div class="bg-[#111] border border-[#333] rounded-xl w-full max-w-md overflow-hidden">
            <div class="p-4 border-b border-[#222]">
                <h3 class="text-lg font-semibold text-white">Set Your Display Name</h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-400 mb-2">Before you start chatting, choose a display name that visitors will see.</p>
                <p class="text-xs text-gray-500 mb-6">This helps keep your real identity private. You can change it anytime in Profile Settings.</p>
                <form id="pseudo-name-setup-form" onsubmit="event.preventDefault(); savePseudoName(this);">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Display Name</label>
                    <input type="text" name="pseudo_name" placeholder="e.g. Support Sarah, Tech Tom" required
                        class="w-full bg-[#222] border border-[#333] rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-[#fe9e00] mb-4">
                    <button type="submit" id="pseudo-name-save-btn"
                        class="w-full bg-[#fe9e00] text-black py-2.5 rounded-lg font-semibold hover:bg-[#e08e00] transition-colors">
                        Save & Continue
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script>
        function savePseudoName(form) {
            const btn = form.querySelector('#pseudo-name-save-btn');
            const name = form.querySelector('input[name="pseudo_name"]').value.trim();
            if (!name) return;
            btn.disabled = true;
            btn.textContent = 'Saving...';
            fetch('{{ route("profile.add.nickname") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ nickname: name, set_active: true })
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('pseudo-name-setup-modal').remove();
            })
            .catch(() => {
                btn.disabled = false;
                btn.textContent = 'Save & Continue';
            });
        }
    </script>
    @endif

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
                        $visitorChat = $session->chats->first() ?? $session->visitor->chats->first();
                        $isCurrentChat = isset($chat) && $visitorChat && $visitorChat->id === $chat->id;
                    @endphp
                    @if($visitorChat)
                        {{-- Visitor has a chat - link to it --}}
                        <a href="{{ route('inbox.chat', $visitorChat) }}" id="chat-item-{{ $visitorChat->id }}" data-visitor-id="{{ $session->visitor_id }}" data-session-id="{{ $session->id }}"
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
                        <div onclick="initiateChat({{ $session->id }})" id="session-item-{{ $session->id }}" data-visitor-id="{{ $session->visitor_id }}" data-session-id="{{ $session->id }}"
                           class="cursor-pointer block p-4 border-b border-[#222] hover:bg-[#1a1a1a]">
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
                        </div>
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
                <a href="{{ route('inbox.chat', $recentChat) }}" id="chat-item-{{ $recentChat->id }}"
                   data-session-id="{{ $recentChat->visitor_session_id }}"
                   class="block p-4 border-b border-[#222] hover:bg-[#1a1a1a] {{ isset($chat) && $recentChat->id === $chat->id ? 'bg-[#1a1a1a]' : '' }}">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-[#fe9e00]/20 rounded-full flex items-center justify-center shrink-0">
                            <span class="text-[#fe9e00] font-semibold text-sm">{{ strtoupper(substr($recentChat->visitor->name ?? 'A', 0, 2)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium truncate flex items-center gap-2">
                                    {{ $recentChat->visitor->name ?? 'Anonymous' }}
                                    @if($recentChat->status === 'closed')
                                        <span class="text-[9px] bg-red-500/10 text-red-500 border border-red-500/20 px-1.5 py-0.5 rounded uppercase font-bold tracking-wider">Closed</span>
                                    @endif
                                </h4>
                                <span class="online-dot w-2 h-2 bg-green-500 rounded-full shrink-0 {{ ($recentChat->visitorSession?->is_online && $recentChat->status !== 'closed') ? '' : 'hidden' }}"></span>
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
                        @if($message->sender_type === 'agent')
                            <div class="text-xs text-gray-500 mb-1 text-right mr-2">{{ $message->sender_name ?? 'Agent' }}</div>
                        @endif
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
                @if(!$isParticipant)
                    {{-- Read-Only Banner + Join Button --}}
                    <div class="flex items-center justify-between bg-[#fe9e00]/10 border border-[#fe9e00]/30 rounded-lg p-3">
                        <div>
                            <span class="text-[#fe9e00] font-medium text-sm block">Read-Only Mode</span>
                            <span class="text-gray-400 text-xs">You must join this chat to send messages.</span>
                        </div>
                        <button type="button" onclick="document.getElementById('join-chat-modal').classList.remove('hidden')"
                            class="bg-[#fe9e00] text-black px-5 py-2 rounded-full font-medium hover:bg-[#e08e00] transition-colors text-sm shrink-0">
                            Join Chat
                        </button>
                    </div>

                    {{-- Join Chat Modal (fixed overlay, outside the flex flow) --}}
                    <div id="join-chat-modal" class="hidden fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4"
                         x-data="{ pseudoName: '{{ Auth::user()->active_pseudo_name ?? Auth::user()->name }}', newName: '', mode: '{{ count(Auth::user()->pseudo_names ?? []) > 0 ? 'select' : 'new' }}' }">
                        <div class="bg-[#111] border border-[#333] rounded-xl w-full max-w-md overflow-hidden relative">
                            <div class="p-4 border-b border-[#222]">
                                <h3 class="text-lg font-semibold text-white">Join Chat</h3>
                            </div>
                            <div class="p-6">
                                <p class="text-sm text-gray-400 mb-6">Are you sure you want to join this chat? Choose a profile name visible to the visitor.</p>
                                <form id="perform-join-form" onsubmit="event.preventDefault(); window.performJoinChat(this);">
                                    @if(count(Auth::user()->pseudo_names ?? []) > 0)
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-400 mb-2">Select Profile Name</label>
                                        <select x-show="mode === 'select'" x-model="pseudoName"
                                            class="w-full bg-[#222] border border-[#333] rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-[#fe9e00]">
                                            @foreach(Auth::user()->pseudo_names as $pn)
                                                <option value="{{ $pn }}">{{ $pn }}</option>
                                            @endforeach
                                        </select>
                                        <div class="text-right mt-2" x-show="mode === 'select'">
                                            <button type="button" @click="mode = 'new'; pseudoName = ''" class="text-xs text-[#fe9e00] hover:underline">+ Create new name</button>
                                        </div>
                                    </div>
                                    @endif
                                    <div x-show="mode === 'new'">
                                        <label class="block text-sm font-medium text-gray-400 mb-2">Create Profile Name</label>
                                        <input type="text" x-model="newName" placeholder="e.g. Support Sarah"
                                            class="w-full bg-[#222] border border-[#333] rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-[#fe9e00]">
                                        <p class="text-xs text-gray-500 mt-2">This name will be saved for future use.</p>
                                        @if(count(Auth::user()->pseudo_names ?? []) > 0)
                                        <div class="text-right mt-2">
                                            <button type="button" @click="mode = 'select'; pseudoName = '{{ Auth::user()->active_pseudo_name ?? Auth::user()->name }}'"
                                                class="text-xs text-gray-400 hover:text-white underline">Cancel &amp; Select Existing</button>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="mt-6 flex items-center justify-end gap-3">
                                        <button type="button" onclick="document.getElementById('join-chat-modal').classList.add('hidden')"
                                            class="px-4 py-2 text-sm text-gray-400 hover:text-white font-medium">Cancel</button>
                                        <button type="submit"
                                            class="bg-[#fe9e00] text-black px-6 py-2 rounded-full font-medium hover:bg-[#e08e00] transition-colors">Join Chat</button>
                                    </div>
                                    <input type="hidden" name="final_pseudo_name" :value="mode === 'new' ? newName : pseudoName">
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Canned Response Dropdown --}}
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
                        <input type="text" id="message-input" 
                            placeholder="{{ $chat->status === 'active' ? 'Type a message... (type / for quick replies)' : 'Chat has been ended' }}" 
                            {{ $chat->status !== 'active' ? 'disabled' : '' }}
                            class="flex-1 bg-[#222] border border-[#333] rounded-full px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-[#fe9e00]">
                        <button type="button" id="attach-btn" 
                            {{ $chat->status !== 'active' ? 'disabled' : '' }}
                            class="p-2 text-gray-400 hover:text-[#fe9e00] disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                        </button>
                        <button type="submit" 
                            {{ $chat->status !== 'active' ? 'disabled' : '' }}
                            class="bg-[#fe9e00] text-black px-6 py-2 rounded-full font-medium hover:bg-[#e08e00] disabled:opacity-50 disabled:cursor-not-allowed">
                            Send
                        </button>
                        <input type="file" id="file-input" style="display: none;" accept="image/*,.pdf,.doc,.docx" {{ $chat->status !== 'active' ? 'disabled' : '' }}>
                    </form>
                @endif
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
                            <p class="text-sm" id="visitor-name">{{ $chat->visitor->name ?? 'Anonymous' }}</p>
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
                            <p class="text-sm" id="visitor-email">{{ $chat->visitor->email ?? 'Not provided' }}</p>
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
                            <p class="text-sm" id="visitor-phone">{{ $chat->visitor->phone ?? 'Not provided' }}</p>
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

        // Handle connection state changes
        let wasDisconnected = false;
        pusher.connection.bind('state_change', function(states) {
            const banner = document.getElementById('connection-banner');
            if (states.current === 'connected') {
                banner.classList.add('hidden');
                if (wasDisconnected) {
                    window.location.reload();
                }
            } else if (states.current === 'disconnected' || states.current === 'unavailable') {
                banner.classList.remove('hidden');
                wasDisconnected = true;
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

        // Request notification permission if default
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        // Subscribe to monitoring channel for new visitors (Unified with Admin Dashboard)
        const monitoringChannel = pusher.subscribe('monitoring');
        monitoringChannel.bind('visitor.joined', function(data) {
             console.log('New visitor joined:', data);
             playNotificationSound();

             // In-app slider notification
             const visitorName = data.visitor?.name || 'Anonymous';
             const country = data.visitor?.location?.country || 'Unknown';
             const sessionId = data.session?.id;
             showToast({
                 title: 'New Visitor',
                 body: `${visitorName} from ${country}`,
                 icon: (visitorName).substring(0,2).toUpperCase(),
                 onClick: sessionId ? function() { initiateChat(sessionId); } : null,
             });

             if ('Notification' in window && Notification.permission === 'granted') {
                 const notification = new Notification('New Visitor 🔔', {
                     body: `New visitor from ${data.visitor.location.country || 'Unknown'}`,
                     icon: '/favicon.ico',
                     tag: 'new-visitor-' + (data.session ? data.session.id : Date.now())
                 });
                 notification.onclick = function() {
                     window.focus();
                     window.location.href = '{{ route("admin.visitors.index") }}?session=' + (data.session ? data.session.id : '');
                 };
             }

             // Add to Active Visitors list (Status Item)
             if (data.session && data.session.id) {
                 const list = document.getElementById('list-active');
                 const sessionId = data.session.id;
                 const itemId = 'session-item-' + sessionId;
                 
                 // Remove "No active visitors" message if exists
                 const emptyMsg = list.querySelector('.text-center');
                 if (emptyMsg && emptyMsg.innerText.includes('No active visitors')) emptyMsg.remove();
                 
                     // Check if visitor already exists in list (deduplicate)
                     if (list.querySelector(`[data-visitor-id="${data.visitor.id}"]`)) {
                         return; 
                     }

                     if (!document.getElementById(itemId)) {
                         const div = document.createElement('div');
                         div.innerHTML = `
                            <div onclick="initiateChat(${sessionId})" id="${itemId}" data-visitor-id="${data.visitor.id}" class="cursor-pointer block p-4 border-b border-[#222] hover:bg-[#1a1a1a] transition-colors duration-1000 ease-out bg-green-500/10">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 bg-[#fe9e00]/20 rounded-full flex items-center justify-center shrink-0">
                                        <span class="text-[#fe9e00] font-semibold text-sm">${(data.visitor.name || 'A').substring(0,2).toUpperCase()}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <h4 class="font-medium truncate">${data.visitor.name || 'Anonymous'}</h4>
                                            <span class="w-2 h-2 bg-green-500 rounded-full shrink-0"></span>
                                        </div>
                                        <p class="text-xs text-gray-500 truncate">${data.visitor.location.country || 'Unknown Location'}</p>
                                        <p class="text-xs text-gray-400 truncate mt-1">Click to start chat</p>
                                    </div>
                                </div>
                            </div>
                         `;
                         const newItem = div.firstElementChild;
                         list.insertBefore(newItem, list.firstChild);
                         setTimeout(() => newItem.classList.remove('bg-green-500/10'), 2000);
                     }
                 }
            });

            // Stop ringing when agent joins a chat (broadcast from AgentJoinedChat event)
            monitoringChannel.bind('agent.joined', function(data) {
                if (data.chat_id) {
                    stopRinging(data.chat_id);
                }
            });

            // Function to initiate chat via POST
            window.initiateChat = function(sessionId) {
                stopRinging(); // Stop all ringing when navigating to a chat
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/inbox/session/' + sessionId;
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrfInput);
                
                document.body.appendChild(form);
                form.submit();
            };


        monitoringChannel.bind('visitor.updated', function(data) {
             console.log('Visitor updated:', data);
             if (data.chat && (data.chat.status === 'active' || data.chat.status === 'waiting')) {
                 const list = document.getElementById('list-active');
                 const chatUrl = '/dashboard/inbox/' + data.chat.id;
                 const itemId = 'chat-item-' + data.chat.id;
                 
                 // Check if exists
                 let existingItem = document.getElementById(itemId);
                 
                 if (!existingItem) {
                     // Create new item
                     const div = document.createElement('div');
                     div.innerHTML = `
                        <a href="${chatUrl}" id="${itemId}" class="block p-4 border-b border-[#222] hover:bg-[#1a1a1a] transition-colors duration-1000 ease-out bg-[#fe9e00]/20">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-[#fe9e00]/20 rounded-full flex items-center justify-center shrink-0">
                                    <span class="text-[#fe9e00] font-semibold text-sm">${(data.visitor.name || 'A').substring(0,2).toUpperCase()}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium truncate">${data.visitor.name || 'Anonymous'}</h4>
                                        <span class="w-2 h-2 bg-green-500 rounded-full shrink-0"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 truncate">${data.chat.client_name || 'Client'}</p>
                                    <p class="text-sm text-gray-400 truncate mt-1">Active now</p>
                                </div>
                            </div>
                        </a>
                     `;
                     const newItem = div.firstElementChild;
                     list.insertBefore(newItem, list.firstChild);
                     
                     // Remove highlight after 2s
                     setTimeout(() => {
                         newItem.classList.remove('bg-[#fe9e00]/20');
                     }, 2000);
                     
                     // Start ringing for waiting chats
                     if (data.chat.status === 'waiting') {
                         newItem.setAttribute('data-ringing-chat', data.chat.id);
                         startRinging(data.chat.id);
                         showToast({
                             title: 'New Chat Waiting',
                             body: `${data.visitor.name || 'Anonymous'} is waiting for an agent`,
                             icon: (data.visitor.name || 'A').substring(0,2).toUpperCase(),
                             onClick: function() { window.location.href = chatUrl; },
                             duration: 10000,
                         });
                     } else {
                         playNotificationSound();
                     }
                 } else {
                    // Update existing item info
                    const nameEl = existingItem.querySelector('h4');
                    if (nameEl) nameEl.textContent = data.visitor.name || 'Anonymous';
                 }

                 // Also add/update in All Chats tab
                 const allList = document.getElementById('list-all');
                 const allItemId = 'chat-item-' + data.chat.id;
                 let allExisting = document.getElementById(allItemId);
                 // Only add if not already in the list (it may already exist from page load)
                 if (!allExisting && allList) {
                     const allEmpty = allList.querySelector('.text-center');
                     if (allEmpty && allEmpty.innerText.includes('No chats')) allEmpty.remove();
                     const div = document.createElement('div');
                     div.innerHTML = `
                        <a href="${chatUrl}" id="${allItemId}" class="block p-4 border-b border-[#222] hover:bg-[#1a1a1a] transition-colors duration-1000 ease-out bg-[#fe9e00]/20">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-[#fe9e00]/20 rounded-full flex items-center justify-center shrink-0">
                                    <span class="text-[#fe9e00] font-semibold text-sm">${(data.visitor.name || 'A').substring(0,2).toUpperCase()}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium truncate">${data.visitor.name || 'Anonymous'}</h4>
                                        <span class="w-2 h-2 bg-green-500 rounded-full shrink-0"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 truncate">${data.chat.client_name || 'Client'}</p>
                                    <p class="text-sm text-gray-400 truncate mt-1">Just now</p>
                                </div>
                            </div>
                        </a>
                     `;
                     const newAllItem = div.firstElementChild;
                     allList.insertBefore(newAllItem, allList.firstChild);
                     setTimeout(() => newAllItem.classList.remove('bg-[#fe9e00]/20'), 2000);
                 }
             }
        });

        monitoringChannel.bind('status.changed', function(data) {
             console.log('Status changed:', data);
             if (!data.is_online) {
                 // Fade out from Active Visitors list (hide, don't remove — visitor may return)
                 const items = document.querySelectorAll(`#list-active [data-session-id="${data.session_id}"], #list-active [data-visitor-id="${data.visitor_id}"]`);
                 items.forEach(item => {
                     item.style.transition = 'opacity 0.5s';
                     item.style.opacity = '0';
                     setTimeout(() => { item.style.display = 'none'; }, 500);
                 });
                 // Hide green dots in All Chats sidebar
                 document.querySelectorAll(`[data-session-id="${data.session_id}"] .online-dot`).forEach(dot => {
                     dot.classList.add('hidden');
                 });
                 // Update header if viewing this visitor's chat
                 @if(isset($chat) && $chat && $chat->visitorSession)
                 if (data.session_id == {{ $chat->visitorSession->id }}) {
                     const statusEl = document.getElementById('visitor-status');
                     if (statusEl) {
                         const clientName = '{{ $chat->client->name }}';
                         statusEl.innerHTML = clientName + ' • <span>Last seen just now</span>';
                     }
                     const headerDot = document.getElementById('current-page-indicator');
                     if (headerDot) headerDot.classList.add('hidden');
                 }
                 @endif
             } else {
                 // Visitor came back online — restore in Active Visitors list
                 let restored = false;
                 const hiddenItems = document.querySelectorAll(`#list-active [data-session-id="${data.session_id}"], #list-active [data-visitor-id="${data.visitor_id}"]`);
                 hiddenItems.forEach(item => {
                     item.style.display = '';
                     item.style.opacity = '1';
                     restored = true;
                 });

                 // Restore green dots in All Chats sidebar
                 document.querySelectorAll(`[data-session-id="${data.session_id}"] .online-dot`).forEach(dot => {
                     dot.classList.remove('hidden');
                 });

                 // Update header if viewing this visitor's chat
                 @if(isset($chat) && $chat && $chat->visitorSession)
                 if (data.session_id == {{ $chat->visitorSession->id }}) {
                     const statusEl = document.getElementById('visitor-status');
                     if (statusEl) {
                         const clientName = '{{ $chat->client->name }}';
                         statusEl.innerHTML = clientName + ' • <span class="text-green-400">Online</span>';
                     }
                     const headerDot = document.getElementById('current-page-indicator');
                     if (headerDot) headerDot.classList.remove('hidden');
                 }
                 @endif
             }
         });

        @if(isset($chat) && $chat)
        const chatId = {{ $chat->id }}; // Must use numeric ID to match broadcast channel
        const sessionId = {{ $chat->visitorSession?->id ?? 'null' }};

        // Subscribe to chat channel (public)
        const chatChannel = pusher.subscribe('chat.' + chatId);
        
        chatChannel.bind('message.sent', function(data) {
            console.log('Received message event:', data);
            // Show visitor messages AND other agents' messages (avoid echo for self)
            const isMe = data.sender_type === 'agent' && data.sender_id == userId;
            
            if (!isMe) {
                addMessage(data);
                playNotificationSound();
                if (document.visibilityState === 'visible') {
                    markAsRead();
                } else {
                    // Update tab title with unread count
                    window.unreadCount = (window.unreadCount || 0) + 1;
                    document.title = `(${window.unreadCount}) Live Chat`;
                }

                // Visitor sent a message — they're definitely online, update status
                if (data.sender_type === 'visitor') {
                    const statusEl = document.getElementById('visitor-status');
                    if (statusEl && !statusEl.innerHTML.includes('Online')) {
                        const clientName = '{{ $chat->client->name ?? "" }}';
                        statusEl.innerHTML = clientName + ' • <span class="text-green-400">Online</span>';
                    }
                    const headerDot = document.getElementById('current-page-indicator');
                    if (headerDot) headerDot.classList.remove('hidden');
                }
            }
        });

        chatChannel.bind('visitor.updated', function(data) {
            console.log('Visitor info updated:', data);
            const nameEl = document.getElementById('visitor-name');
            const emailEl = document.getElementById('visitor-email');
            const phoneEl = document.getElementById('visitor-phone');
            
            if (nameEl) nameEl.textContent = data.visitor.name || 'Anonymous';
            if (emailEl) emailEl.textContent = data.visitor.email || 'Not provided';
            if (phoneEl) phoneEl.textContent = data.visitor.phone || 'Not provided';
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

        chatChannel.bind('chat.closed', function(data) {
            // Update status badge
            const badge = document.getElementById('chat-status-badge');
            if (badge) {
                badge.textContent = 'Closed';
                badge.className = 'px-3 py-1 text-xs rounded-full bg-red-500/20 text-red-400';
            }
            // Remove end chat button
            const endBtn = document.getElementById('end-chat-btn');
            if (endBtn) endBtn.remove();
            // Disable message input
            const input = document.getElementById('message-input');
            if (input) {
                input.disabled = true;
                input.placeholder = 'Chat has been ended';
            }
            const sendBtn = document.querySelector('#message-form button[type="submit"]');
            if (sendBtn) sendBtn.disabled = true;
            const attachBtn = document.getElementById('attach-btn');
            if (attachBtn) attachBtn.disabled = true;
            // Show system message
            const container = document.getElementById('messages-container');
            const sysMsg = document.createElement('div');
            sysMsg.className = 'mb-4 flex justify-center';
            sysMsg.innerHTML = `<span class="bg-[#222] text-gray-400 px-4 py-1.5 rounded-full text-xs">Chat ended by ${data.ended_by || 'agent'}</span>`;
            const typingIndicator = document.getElementById('typing-indicator');
            if (typingIndicator) {
                container.insertBefore(sysMsg, typingIndicator);
            } else {
                container.appendChild(sysMsg);
            }
            container.scrollTop = container.scrollHeight;
            // Update sidebar item
            const sidebarItem = document.getElementById('chat-item-' + data.chat_id);
            if (sidebarItem) {
                const nameEl = sidebarItem.querySelector('h4');
                if (nameEl && !nameEl.querySelector('.closed-badge')) {
                    nameEl.insertAdjacentHTML('afterbegin', '<span class="closed-badge text-[9px] bg-red-500/10 text-red-500 border border-red-500/20 px-1.5 py-0.5 rounded uppercase font-bold tracking-wider mr-2">Closed</span>');
                }
                // Hide green dot
                const dot = sidebarItem.querySelector('.online-dot');
                if (dot) dot.classList.add('hidden');
            }
        });

        // Subscribe to visitor session for status + page changes
        if (sessionId) {
            const visitorSessionChannel = pusher.subscribe('visitor-session.' + sessionId);

            // Online/offline status in header
            visitorSessionChannel.bind('status.changed', function(data) {
                const statusEl = document.getElementById('visitor-status');
                if (statusEl) {
                    const clientName = '{{ $chat->client->name ?? "" }}';
                    if (data.is_online) {
                        statusEl.innerHTML = clientName + ' • <span class="text-green-400">Online</span>';
                    } else {
                        statusEl.innerHTML = clientName + ' • <span>Last seen just now</span>';
                    }
                }
                const headerDot = document.getElementById('current-page-indicator');
                if (headerDot) {
                    data.is_online ? headerDot.classList.remove('hidden') : headerDot.classList.add('hidden');
                }
            });

            // Page navigation tracking
            visitorSessionChannel.bind('page.changed', function(data) {
                updateCurrentPage(data.page_url, data.page_title);
            });
        }
        @endif

        function addMessage(msg) {
            console.log('Adding message:', msg);
            const container = document.getElementById('messages-container');
            // Skip if message already exists (avoid duplicates from optimistic + WebSocket)
            if (msg.id && document.querySelector(`[data-msg-id="${msg.id}"]`)) return;
            const isVisitor = msg.sender_type === 'visitor';
            const div = document.createElement('div');
            div.className = 'mb-4 flex ' + (isVisitor ? 'justify-start' : 'justify-end');
            div.setAttribute('data-msg-id', msg.id);
            
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
                    ${!isVisitor ? `<div class="text-xs text-gray-500 mb-1 text-right mr-2">${msg.sender_name || 'Agent'}</div>` : ''}
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

        // In-app slider toast notification
        function showToast({ title, body, icon, onClick, duration = 6000 }) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast-slide-in pointer-events-auto cursor-pointer bg-[#1a1a1a] border border-[#333] rounded-xl shadow-2xl p-4 flex items-start gap-3';
            toast.innerHTML = `
                <div class="w-10 h-10 bg-[#fe9e00]/20 rounded-full flex items-center justify-center shrink-0">
                    <span class="text-[#fe9e00] font-semibold text-sm">${icon || '👤'}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white">${title}</p>
                    <p class="text-xs text-gray-400 mt-0.5 truncate">${body}</p>
                </div>
                <button class="text-gray-500 hover:text-white shrink-0 mt-0.5" onclick="event.stopPropagation(); this.closest('.toast-slide-in, .toast-slide-out').classList.add('toast-slide-out'); setTimeout(() => this.closest('.toast-slide-in, .toast-slide-out')?.remove(), 300);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            `;
            if (onClick) {
                toast.addEventListener('click', function() {
                    onClick();
                    toast.classList.add('toast-slide-out');
                    setTimeout(() => toast.remove(), 300);
                });
            }
            container.appendChild(toast);
            // Auto-dismiss
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.classList.add('toast-slide-out');
                    setTimeout(() => toast.remove(), 300);
                }
            }, duration);
        }

        // Ringing system for new waiting chats
        let ringingInterval = null;
        let ringingChats = new Set();

        function playRingTone() {
            try {
                const ctx = initAudioContext();
                if (ctx.state === 'suspended') ctx.resume();

                // Two-tone ring pattern (like a phone)
                const now = ctx.currentTime;
                for (let i = 0; i < 2; i++) {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.frequency.value = i === 0 ? 440 : 520;
                    osc.type = 'sine';
                    gain.gain.setValueAtTime(0, now);
                    // Ring on
                    gain.gain.linearRampToValueAtTime(0.15, now + 0.02);
                    gain.gain.setValueAtTime(0.15, now + 0.4);
                    gain.gain.linearRampToValueAtTime(0, now + 0.42);
                    // Ring on again
                    gain.gain.linearRampToValueAtTime(0.15, now + 0.6);
                    gain.gain.setValueAtTime(0.15, now + 1.0);
                    gain.gain.linearRampToValueAtTime(0, now + 1.02);
                    osc.start(now);
                    osc.stop(now + 1.1);
                }
            } catch (e) {
                console.log('Could not play ring tone:', e);
            }
        }

        function startRinging(chatId) {
            ringingChats.add(chatId);
            updateRingingBanner();
            if (!ringingInterval) {
                playRingTone();
                ringingInterval = setInterval(() => {
                    if (ringingChats.size > 0) {
                        playRingTone();
                    } else {
                        stopRinging();
                    }
                }, 3000);
            }
        }

        function stopRinging(chatId) {
            if (chatId) {
                ringingChats.delete(chatId);
            } else {
                ringingChats.clear();
            }
            if (ringingChats.size === 0 && ringingInterval) {
                clearInterval(ringingInterval);
                ringingInterval = null;
            }
            updateRingingBanner();
        }

        function updateRingingBanner() {
            const banner = document.getElementById('ringing-banner');
            if (!banner) return;
            if (ringingChats.size > 0) {
                const count = ringingChats.size;
                banner.querySelector('.ringing-text').textContent =
                    count === 1 ? '1 new chat waiting — click to answer' : `${count} new chats waiting — click to answer`;
                banner.classList.remove('hidden');
            } else {
                banner.classList.add('hidden');
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

        // Send message (only when message-form exists, i.e. agent is a participant)
        const messageFormEl = document.getElementById('message-form');
        if (messageFormEl) {
        messageFormEl.addEventListener('submit', function(e) {
            e.preventDefault();
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            if (!message) return;

            // Optimistic UI: show message immediately
            const tempId = 'temp-' + Date.now();
            const selectedNickname = document.getElementById('selected-nickname');
            const senderName = selectedNickname ? selectedNickname.textContent : '{{ Auth::user()->active_pseudo_name ?? Auth::user()->name }}';
            addMessage({
                id: tempId,
                sender_type: 'agent',
                message_type: 'text',
                message: message,
                sender_name: senderName,
                created_at: new Date().toISOString()
            });
            input.value = '';

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
                // Update temp message with real ID for read receipt tracking
                const tempEl = document.querySelector(`[data-msg-id="${tempId}"]`);
                if (tempEl) tempEl.setAttribute('data-msg-id', data.message.id);
                const statusEl = document.getElementById('msg-status-' + tempId);
                if (statusEl) statusEl.id = 'msg-status-' + data.message.id;
            })
            .catch(err => {
                console.error('Send error:', err);
                // Mark message as failed
                const tempEl = document.querySelector(`[data-msg-id="${tempId}"]`);
                if (tempEl) {
                    const bubble = tempEl.querySelector('.message-bubble');
                    if (bubble) bubble.classList.add('opacity-50');
                    tempEl.insertAdjacentHTML('beforeend', '<p class="text-xs text-red-400 text-right mt-1">Failed to send</p>');
                }
            });
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
        } // end if (messageFormEl)
        
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
                window.unreadCount = 0;
                document.title = @json(isset($chat) && $chat ? "Chat #{$chat->id} - " . ($chat->visitor->name ?? 'Anonymous') : 'Live Chat');
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

        if (messageInput) {
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
        } // end if (messageInput)

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
        // End Chat function
        function endChat() {
            if (!confirm('Are you sure you want to end this chat?')) return;
            
            fetch('/dashboard/chat/' + chatId + '/close', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update status badge
                    const badge = document.getElementById('chat-status-badge');
                    if (badge) {
                        badge.textContent = 'Closed';
                        badge.className = 'px-3 py-1 text-xs rounded-full bg-red-500/20 text-red-400';
                    }
                    // Hide end chat button
                    const btn = document.getElementById('end-chat-btn');
                    if (btn) btn.remove();
                    // Disable message input
                    const input = document.getElementById('message-input');
                    if (input) {
                        input.disabled = true;
                        input.placeholder = 'Chat has been ended';
                    }
                    const sendBtn = document.querySelector('#message-form button[type="submit"]');
                    if (sendBtn) sendBtn.disabled = true;
                }
            })
            .catch(err => console.error('End chat error:', err));
        }

        @if(!$isParticipant)
        window.performJoinChat = function(form) {
            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Joining...';
            const pName = (form.querySelector('input[name="final_pseudo_name"]').value || '').trim();
            if (!pName) {
                alert('Please provide a profile name before joining.');
                btn.disabled = false;
                btn.textContent = 'Join Chat';
                return;
            }
            fetch(`/dashboard/chat/${chatId}/join`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ pseudo_name: pName })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else if (data.error) {
                    alert(data.error);
                    btn.disabled = false;
                    btn.textContent = 'Join Chat';
                }
            })
            .catch(err => {
                console.error(err);
                btn.disabled = false;
                btn.textContent = 'Join Chat';
            });
        };
        @endif
        @endif
    </script>
</body>
</html>
