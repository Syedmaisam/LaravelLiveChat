@extends('layouts.admin')

@section('title', 'Inbox')

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-6">
    <div class="bg-[#111] border border-[#222] rounded-lg p-5">
        <div class="text-2xl font-bold text-[#fe9e00]" id="stat-waiting">{{ $waitingChats->count() }}</div>
        <div class="text-sm text-gray-500 mt-1">Waiting Chats</div>
    </div>
    <div class="bg-[#111] border border-[#222] rounded-lg p-5">
        <div class="text-2xl font-bold text-[#fe9e00]" id="stat-active">{{ $activeChats->count() }}</div>
        <div class="text-sm text-gray-500 mt-1">Active Chats</div>
    </div>
    <div class="bg-[#111] border border-[#222] rounded-lg p-5">
        <div class="text-2xl font-bold text-[#fe9e00]" id="stat-online">{{ $onlineVisitors->count() }}</div>
        <div class="text-sm text-gray-500 mt-1">Online Visitors</div>
    </div>
    <div class="bg-[#111] border border-[#222] rounded-lg p-5">
        <div class="text-2xl font-bold text-white" id="stat-total">{{ $totalChats ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-1">Total Chats Today</div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
    <!-- Waiting Chats -->
    <div class="bg-[#111] border border-[#222] rounded-lg">
        <div class="px-5 py-4 border-b border-[#222] flex items-center justify-between">
            <h3 class="font-semibold">Waiting</h3>
            <span class="bg-red-500/20 text-red-400 text-xs font-bold px-2 py-0.5 rounded">{{ $waitingChats->count() }}</span>
        </div>
        <div class="divide-y divide-[#222] max-h-80 overflow-y-auto">
            @forelse($waitingChats as $chat)
            <a href="{{ route('inbox.chat', $chat) }}" class="block px-5 py-3 hover:bg-[#1a1a1a]">
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
            <a href="{{ route('inbox.chat', $chat) }}" class="block px-5 py-3 hover:bg-[#1a1a1a]">
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

<!-- All Chats List -->
<div class="mt-6 bg-[#111] border border-[#222] rounded-lg">
    <div class="px-5 py-4 border-b border-[#222] flex items-center justify-between">
        <h3 class="font-semibold">All Conversations</h3>
        <div class="flex items-center gap-2">
            <select id="label-filter" onchange="filterChats()" class="bg-black border border-[#333] rounded px-3 py-1 text-xs text-white focus:border-[#fe9e00] focus:outline-none">
                <option value="">All Labels</option>
                <option value="new">New</option>
                <option value="pending">Pending</option>
                <option value="converted">Converted</option>
                <option value="no_response">No Response</option>
                <option value="closed">Closed</option>
            </select>
        </div>
    </div>
    <div class="divide-y divide-[#222] max-h-[500px] overflow-y-auto" id="chat-list">
        @forelse($allChats as $chat)
        <a href="{{ route('inbox.chat', $chat['uuid']) }}" 
           class="block px-5 py-3 hover:bg-[#1a1a1a] chat-item" 
           data-chat-id="{{ $chat['uuid'] }}"
           data-label="{{ $chat['label'] }}"
           data-status="{{ $chat['status'] }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center flex-1 min-w-0">
                    <!-- Online indicator + Avatar -->
                    <div class="relative mr-3 shrink-0">
                        <div class="w-10 h-10 rounded-full bg-[#222] flex items-center justify-center text-sm font-bold text-[#fe9e00]">
                            {{ strtoupper(substr($chat['visitor_name'], 0, 1)) }}
                        </div>
                        @if($chat['is_online'])
                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 rounded-full border-2 border-[#111]"></span>
                        @endif
                    </div>
                    
                    <!-- Chat info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-sm truncate">{{ $chat['visitor_name'] }}</span>
                            <!-- Status badge -->
                            @if($chat['status'] === 'waiting')
                            <span class="px-1.5 py-0.5 bg-red-500/20 text-red-400 text-[10px] font-medium rounded">WAITING</span>
                            @endif
                            <!-- Label badge -->
                            @if($chat['label'])
                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded
                                @if($chat['label'] === 'new') bg-blue-500/20 text-blue-400
                                @elseif($chat['label'] === 'pending') bg-yellow-500/20 text-yellow-400
                                @elseif($chat['label'] === 'converted') bg-green-500/20 text-green-400
                                @elseif($chat['label'] === 'no_response') bg-gray-500/20 text-gray-400
                                @else bg-[#222] text-gray-500
                                @endif">
                                {{ strtoupper(str_replace('_', ' ', $chat['label'])) }}
                            </span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 truncate mt-0.5 last-message">
                            @if($chat['last_message'])
                            {{ $chat['last_message'] }}
                            @else
                            <span class="italic">No messages yet</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Right side: time & unread -->
                <div class="text-right ml-3 shrink-0">
                    <div class="text-[10px] text-gray-500">
                        {{ $chat['last_message_at'] ? \Carbon\Carbon::parse($chat['last_message_at'])->diffForHumans(null, true) : '' }}
                    </div>
                    @if($chat['unread_count'] > 0)
                    <span class="inline-flex items-center justify-center w-5 h-5 bg-[#fe9e00] text-black text-[10px] font-bold rounded-full mt-1">
                        {{ $chat['unread_count'] > 9 ? '9+' : $chat['unread_count'] }}
                    </span>
                    @endif
                </div>
            </div>
        </a>
        @empty
        <div class="px-5 py-12 text-center text-gray-500">
            <p>No conversations yet</p>
        </div>
        @endforelse
    </div>
</div>

<script>
// Chat filtering
function filterChats() {
    const label = document.getElementById('label-filter').value;
    document.querySelectorAll('.chat-item').forEach(item => {
        if (!label || item.dataset.label === label) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

// Real-time dashboard updates
document.addEventListener('DOMContentLoaded', function() {
    if (!window.reverbClient) {
        console.log('Reverb client not available for real-time updates');
        return;
    }
    
    console.log('Setting up real-time dashboard updates...');
    
    // Store original stats for animation
    let stats = {
        waiting: {{ $waitingChats->count() }},
        active: {{ $activeChats->count() }},
        online: {{ $onlineVisitors->count() }},
        total: {{ $totalChats ?? 0 }}
    };
    
    // Subscribe to monitoring channel for visitor status changes
    const monitoringChannel = window.reverbClient.subscribe('monitoring');
    
    monitoringChannel.bind('visitor.status.changed', function(data) {
        console.log('Visitor status changed:', data);
        if (data.is_online) {
            stats.online++;
        } else {
            stats.online = Math.max(0, stats.online - 1);
        }
        updateStatsDisplay();
        // Could also dynamically add/remove visitor from the list
    });
    
    monitoringChannel.bind('chat.created', function(data) {
        console.log('New chat created:', data);
        stats.waiting++;
        stats.total++;
        updateStatsDisplay();
        
        // Add new chat to the waiting list
        addChatToList('waiting-list', data);
        
        // Show notification
        if (window.showNotification) {
            window.showNotification('New Chat', `${data.visitor_name || 'New visitor'} started a chat`, `/inbox/${data.chat_id}`);
        }
    });
    
    monitoringChannel.bind('chat.status.changed', function(data) {
        console.log('Chat status changed:', data);
        if (data.old_status === 'waiting' && data.new_status === 'active') {
            stats.waiting = Math.max(0, stats.waiting - 1);
            stats.active++;
        } else if (data.new_status === 'closed') {
            if (data.old_status === 'waiting') {
                stats.waiting = Math.max(0, stats.waiting - 1);
            } else if (data.old_status === 'active') {
                stats.active = Math.max(0, stats.active - 1);
            }
        }
        updateStatsDisplay();
        // Move chat between lists
        moveChatBetweenLists(data);
    });
    
    // Subscribe to agent channel for new messages
    const userId = {{ Auth::id() }};
    const agentChannel = window.reverbClient.subscribe('private-agent.' + userId);
    
    agentChannel.bind('new.message', function(data) {
        console.log('New message received:', data);
        // Update unread count in chat list
        updateChatUnreadCount(data.chat_id, data.unread_count);
        // Update last message preview
        updateChatLastMessage(data.chat_id, data.message);
    });
    
    function updateStatsDisplay() {
        // Update stat cards with animation
        animateValue('stat-waiting', stats.waiting);
        animateValue('stat-active', stats.active);
        animateValue('stat-online', stats.online);
        animateValue('stat-total', stats.total);
        
        // Update badge counts
        const waitingBadge = document.getElementById('waiting-badge');
        if (waitingBadge) {
            waitingBadge.textContent = stats.waiting;
        }
        const activeBadge = document.getElementById('active-badge');
        if (activeBadge) {
            activeBadge.textContent = stats.active;
        }
    }
    
    function animateValue(elementId, newValue) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const currentValue = parseInt(element.textContent) || 0;
        if (currentValue === newValue) return;
        
        // Flash animation
        element.style.transition = 'transform 0.15s ease, color 0.15s ease';
        element.style.transform = 'scale(1.2)';
        element.style.color = newValue > currentValue ? '#22c55e' : '#ef4444';
        
        element.textContent = newValue;
        
        setTimeout(() => {
            element.style.transform = 'scale(1)';
            element.style.color = '';
        }, 150);
    }
    
    function addChatToList(listId, chatData) {
        // For simplicity, just refresh the page or add a visual indicator
        // A full implementation would dynamically insert HTML
        const waitingSection = document.querySelector('[data-section="waiting"]');
        if (waitingSection) {
            const emptyMessage = waitingSection.querySelector('.text-center');
            if (emptyMessage) {
                emptyMessage.remove();
            }
        }
    }
    
    function moveChatBetweenLists(data) {
        // For simplicity, highlight the changed item
        // A full implementation would move HTML elements between sections
    }
    
    function updateChatUnreadCount(chatId, count) {
        const chatItem = document.querySelector(`[data-chat-id="${chatId}"]`);
        if (!chatItem) return;
        
        let badge = chatItem.querySelector('.unread-badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'unread-badge inline-flex items-center justify-center w-5 h-5 bg-[#fe9e00] text-black text-[10px] font-bold rounded-full';
                chatItem.querySelector('.text-right')?.appendChild(badge);
            }
            badge.textContent = count > 9 ? '9+' : count;
        } else if (badge) {
            badge.remove();
        }
    }
    
    function updateChatLastMessage(chatId, message) {
        const chatItem = document.querySelector(`[data-chat-id="${chatId}"]`);
        if (!chatItem) return;
        
        const lastMessageEl = chatItem.querySelector('.last-message');
        if (lastMessageEl) {
            lastMessageEl.textContent = message.substring(0, 50) + (message.length > 50 ? '...' : '');
            lastMessageEl.classList.remove('italic');
        }
    }
    
    // Auto-refresh every 60 seconds as a fallback
    setInterval(() => {
        // Just update timestamps and online indicators
        document.querySelectorAll('[data-timestamp]').forEach(el => {
            // Could implement relative time updates here
        });
    }, 60000);
});
</script>

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
