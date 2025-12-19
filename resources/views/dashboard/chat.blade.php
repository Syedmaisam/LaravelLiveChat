<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat #{{ $chat->id }} - {{ $chat->visitor->name ?? 'Anonymous' }}</title>
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
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[#fe9e00]/20 rounded-full flex items-center justify-center">
                    <span class="text-[#fe9e00] font-semibold text-sm">{{ strtoupper(substr($chat->visitor->name ?? 'A', 0, 2)) }}</span>
                </div>
                <div>
                    <h1 class="font-semibold">{{ $chat->visitor->name ?? 'Anonymous Visitor' }}</h1>
                    <p class="text-xs text-gray-500">{{ $chat->client->name }} • {{ $chat->visitorSession?->is_online ? 'Online' : 'Offline' }}</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 text-xs rounded-full {{ $chat->status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400' }}">
                {{ ucfirst($chat->status) }}
            </span>
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-white text-sm">Logout</button>
            </form>
        </div>
    </header>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Chat Area (Center) -->
        <div class="flex-1 flex flex-col">
            <!-- Messages -->
            <div class="flex-1 overflow-y-auto p-4 messages-container" id="messages-container">
                @foreach($messages as $message)
                <div class="mb-4 flex {{ $message->sender_type === 'visitor' ? 'justify-start' : 'justify-end' }}">
                    <div class="max-w-[70%]">
                        <div class="message-bubble px-4 py-2 rounded-2xl {{ $message->sender_type === 'visitor' ? 'bg-[#222] text-white' : 'bg-[#fe9e00] text-black' }}">
                            @if($message->message_type === 'file')
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                    </svg>
                                    <span>{{ $message->file_name }}</span>
                                </div>
                            @else
                                {{ $message->message }}
                            @endif
                        </div>
                        <div class="flex items-center gap-1 mt-1 {{ $message->sender_type === 'visitor' ? 'justify-start' : 'justify-end' }}">
                            <span class="text-xs text-gray-500">{{ $message->created_at->format('H:i') }}</span>
                            @if($message->sender_type === 'agent')
                                @if($message->read_at)
                                    <svg class="w-4 h-4 text-[#fe9e00]" fill="currentColor" viewBox="0 0 24 24" title="Read at {{ $message->read_at->format('H:i') }}">
                                        <path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12l-1.41 1.41L11.66 19l12-12-1.42-1.41zM.41 13.41L6 19l1.41-1.41L1.83 12 .41 13.41z"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 24 24" title="Sent">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                    </svg>
                                @endif
                            @endif
                        </div>
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
            <div class="border-t border-[#222] p-4 bg-[#111]">
                <form id="message-form" class="flex gap-3">
                    <input type="text" id="message-input" placeholder="Type a message..." 
                        class="flex-1 bg-[#222] border border-[#333] rounded-full px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-[#fe9e00]">
                    <button type="button" id="attach-btn" class="p-2 text-gray-400 hover:text-[#fe9e00]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                    </button>
                    <button type="submit" class="bg-[#fe9e00] text-black px-6 py-2 rounded-full font-medium hover:bg-[#e08e00]">
                        Send
                    </button>
                </form>
            </div>
        </div>

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
                        <p class="text-sm truncate font-medium">{{ $visit->page_title ?? 'Untitled Page' }}</p>
                        <a href="{{ $visit->page_url }}" target="_blank" class="text-xs text-[#fe9e00] hover:underline truncate block">{{ Str::limit($visit->page_url, 50) }}</a>
                        <p class="text-xs text-gray-500 mt-1">{{ $visit->created_at->format('H:i:s') }}</p>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500">No pages visited</p>
                    @endforelse
                </div>
            </div>
        </div>
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
            cluster: 'mt1'
        });

        const chatId = {{ $chat->id }};
        const sessionId = {{ $chat->visitorSession?->id ?? 'null' }};

        // Subscribe to chat channel (public)
        const chatChannel = pusher.subscribe('chat.' + chatId);
        
        chatChannel.bind('message.sent', function(data) {
            console.log('Received message event:', data);
            // Only show visitor messages (agent messages added optimistically)
            if (data.sender_type === 'visitor') {
                addMessage(data);
            }
        });

        chatChannel.bind('visitor.typing', function(data) {
            showTyping();
        });

        // Subscribe to visitor session for page changes
        if (sessionId) {
            const sessionChannel = pusher.subscribe('visitor-session.' + sessionId);
            sessionChannel.bind('page.changed', function(data) {
                updateCurrentPage(data.page_url, data.page_title);
            });
        }

        function addMessage(msg) {
            console.log('Adding message:', msg);
            const container = document.getElementById('messages-container');
            const isVisitor = msg.sender_type === 'visitor';
            const div = document.createElement('div');
            div.className = 'mb-4 flex ' + (isVisitor ? 'justify-start' : 'justify-end');
            div.innerHTML = `
                <div class="max-w-[70%]">
                    <div class="message-bubble px-4 py-2 rounded-2xl ${isVisitor ? 'bg-[#222] text-white' : 'bg-[#fe9e00] text-black'}">
                        ${msg.message}
                    </div>
                    <p class="text-xs text-gray-500 mt-1 ${isVisitor ? 'text-left' : 'text-right'}">
                        ${new Date(msg.created_at).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}
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

        // Scroll to bottom on load
        document.getElementById('messages-container').scrollTop = document.getElementById('messages-container').scrollHeight;
    </script>
</body>
</html>
