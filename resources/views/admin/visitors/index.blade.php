@extends('layouts.admin')

@section('title', 'Live Visitors')

@section('actions')
<div class="flex items-center space-x-3">
    <select id="client-filter" onchange="filterByClient(this.value)" class="bg-black border border-[#333] rounded px-3 py-1.5 text-sm text-white focus:border-[#fe9e00] focus:outline-none">
        <option value="">All Clients</option>
        @foreach($clients as $client)
        <option value="{{ $client->id }}" {{ $clientId == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
        @endforeach
    </select>
</div>
@endsection

@section('content')
<!-- Tabs -->
<div class="flex items-center space-x-1 mb-6 border-b border-[#222]">
    <a href="?tab=active{{ $clientId ? '&client='.$clientId : '' }}" 
       class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $tab === 'active' ? 'border-[#fe9e00] text-[#fe9e00]' : 'border-transparent text-gray-400 hover:text-white' }}">
        Active <span class="ml-1 px-1.5 py-0.5 text-xs rounded {{ $tab === 'active' ? 'bg-[#fe9e00] text-black' : 'bg-[#222] text-gray-400' }}">{{ $activeCount }}</span>
    </a>
    <a href="?tab=history{{ $clientId ? '&client='.$clientId : '' }}" 
       class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $tab === 'history' ? 'border-[#fe9e00] text-[#fe9e00]' : 'border-transparent text-gray-400 hover:text-white' }}">
        History <span class="ml-1 px-1.5 py-0.5 text-xs rounded {{ $tab === 'history' ? 'bg-[#fe9e00] text-black' : 'bg-[#222] text-gray-400' }}">{{ $historyCount }}</span>
    </a>
</div>

<!-- Visitors Table -->
<div class="bg-[#111] border border-[#222] rounded-lg overflow-hidden">
    <table class="w-full">
        <thead class="bg-[#0a0a0a] border-b border-[#222]">
            <tr>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase w-8"></th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Visitor</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">IP</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Current Page</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Referrer</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Time</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Pages</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#222]" id="visitors-table">
            @forelse($visitors as $session)
            <tr class="hover:bg-[#1a1a1a] cursor-pointer visitor-row" 
                data-session-id="{{ $session->id }}"
                onclick="showVisitorDetail({{ $session->id }})">
                <td class="px-4 py-3">
                    @if($session->is_online)
                    <span class="w-2.5 h-2.5 bg-green-500 rounded-full inline-block"></span>
                    @else
                    <span class="w-2.5 h-2.5 bg-gray-500 rounded-full inline-block"></span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded bg-[#222] flex items-center justify-center text-xs font-bold text-[#fe9e00] mr-3">
                            {{ strtoupper(substr($session->visitor->name ?? 'A', 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-medium text-sm text-white flex items-center gap-2">
                                {{ $session->visitor->name ?? 'Anonymous' }}
                                @if($session->visitor->country_code)
                                <img src="https://flagcdn.com/16x12/{{ strtolower($session->visitor->country_code) }}.png" 
                                     alt="{{ $session->visitor->country }}" 
                                     title="{{ $session->visitor->country }}"
                                     class="inline-block">
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 flex items-center gap-2">
                                <span>{{ $session->visitor->city ?? 'Unknown' }}, {{ $session->visitor->country ?? '' }}</span>
                                @if($session->visitor->os)
                                <span class="text-gray-600">•</span>
                                <span title="{{ $session->visitor->os }}">
                                    @if(str_contains($session->visitor->os, 'Windows'))
                                    🪟
                                    @elseif(str_contains($session->visitor->os, 'macOS'))
                                    🍎
                                    @elseif(str_contains($session->visitor->os, 'Linux'))
                                    🐧
                                    @elseif(str_contains($session->visitor->os, 'Android'))
                                    🤖
                                    @elseif(str_contains($session->visitor->os, 'iOS'))
                                    📱
                                    @else
                                    💻
                                    @endif
                                </span>
                                @endif
                                @if($session->visitor->browser)
                                <span title="{{ $session->visitor->browser }}">
                                    @if(str_contains($session->visitor->browser, 'Chrome'))
                                    🌐
                                    @elseif(str_contains($session->visitor->browser, 'Firefox'))
                                    🦊
                                    @elseif(str_contains($session->visitor->browser, 'Safari'))
                                    🧭
                                    @elseif(str_contains($session->visitor->browser, 'Edge'))
                                    📘
                                    @else
                                    🌍
                                    @endif
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-gray-400 font-mono">{{ $session->visitor->ip_address }}</td>
                <td class="px-4 py-3">
                    <a href="{{ $session->current_page }}" target="_blank" class="text-sm text-[#fe9e00] hover:underline truncate block max-w-xs">
                        {{ Str::limit($session->current_page, 40) }}
                    </a>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500 truncate max-w-xs">
                    {{ $session->referrer_url ? Str::limit($session->referrer_url, 30) : 'Direct' }}
                </td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 bg-[#222] text-gray-400 text-xs rounded">{{ $session->client->name }}</span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-400">{{ $session->started_at->diffForHumans(null, true) }}</td>
                <td class="px-4 py-3 text-sm text-gray-400">{{ $session->pageVisits->count() }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                    No {{ $tab === 'active' ? 'active' : 'past' }} visitors
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($visitors->hasPages())
<div class="mt-4">{{ $visitors->appends(['tab' => $tab, 'client' => $clientId])->links() }}</div>
@endif

<!-- Visitor Detail Panel (Slide-out) -->
<div id="visitor-panel" class="fixed inset-y-0 right-0 w-96 bg-[#111] border-l border-[#222] transform translate-x-full transition-transform duration-300 z-50 flex flex-col" style="display: none;">
    <div class="h-14 border-b border-[#222] flex items-center justify-between px-4">
        <h3 class="font-semibold" id="panel-title">Visitor Details</h3>
        <button onclick="closePanel()" class="text-gray-500 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    
    <div class="flex-1 overflow-y-auto p-4" id="panel-content">
        <!-- Content loaded via JS -->
    </div>
</div>
<div id="panel-overlay" class="fixed inset-0 bg-black/50 z-40" style="display: none;" onclick="closePanel()"></div>

@push('scripts')
<script>
function filterByClient(clientId) {
    const tab = '{{ $tab }}';
    window.location.href = `?tab=${tab}${clientId ? '&client=' + clientId : ''}`;
}

function showVisitorDetail(sessionId) {
    fetch(`/admin/visitors/${sessionId}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('panel-title').textContent = data.visitor.name;
            document.getElementById('panel-content').innerHTML = `
                <div class="text-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-[#222] flex items-center justify-center text-xl font-bold text-[#fe9e00] mx-auto mb-2">
                        ${data.visitor.name.charAt(0).toUpperCase()}
                    </div>
                    <div class="font-semibold text-white">${data.visitor.name}</div>
                    <div class="text-sm text-gray-500">${data.city || ''}, ${data.country || 'Unknown'}</div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs mt-2 ${data.is_online ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400'}">
                        ${data.is_online ? '● Online' : '○ Offline'}
                    </span>
                </div>

                ${data.has_chat ? 
                    `<a href="/dashboard/chat/${data.chat_id}" class="block w-full text-center bg-[#fe9e00] text-black font-medium py-2 rounded mb-4 hover:bg-[#e08e00]">View Chat</a>` : 
                    `<form action="/dashboard/chat/initiate" method="POST" class="mb-4">
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                        <input type="hidden" name="session_id" value="${data.id}">
                        <button type="submit" class="w-full bg-[#fe9e00] text-black font-medium py-2 rounded hover:bg-[#e08e00]">Start Chat</button>
                    </form>`
                }

                <div class="space-y-4">
                    <div class="bg-[#0a0a0a] rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase mb-3">Contact Info</div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Email</span><span class="text-white">${data.visitor.email || '-'}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Phone</span><span class="text-white">${data.visitor.phone || '-'}</span></div>
                        </div>
                    </div>

                    <div class="bg-[#0a0a0a] rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase mb-3">Session Info</div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">IP</span><span class="text-white font-mono">${data.ip}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Device</span><span class="text-white">${data.device || 'Unknown'}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Browser</span><span class="text-white">${data.browser || 'Unknown'}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Duration</span><span class="text-white">${data.duration}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Client</span><span class="text-white">${data.client}</span></div>
                        </div>
                    </div>

                    <div class="bg-[#0a0a0a] rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase mb-3">Pages Visited (${data.page_views.length})</div>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            ${data.page_views.map(pv => `
                                <div class="flex items-center justify-between text-sm py-1 border-b border-[#222] last:border-0">
                                    <a href="${pv.url}" target="_blank" class="text-[#fe9e00] hover:underline truncate max-w-[200px]">${pv.title || pv.url}</a>
                                    <span class="text-gray-500 text-xs">${pv.time}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>

                    <div class="bg-[#0a0a0a] rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase mb-2">Current Page</div>
                        <a href="${data.current_page}" target="_blank" class="text-[#fe9e00] hover:underline text-sm break-all">${data.current_page}</a>
                    </div>
                </div>
            `;
            
            document.getElementById('visitor-panel').style.display = 'flex';
            document.getElementById('panel-overlay').style.display = 'block';
            setTimeout(() => {
                document.getElementById('visitor-panel').classList.remove('translate-x-full');
            }, 10);
        });
}

function closePanel() {
    document.getElementById('visitor-panel').classList.add('translate-x-full');
    document.getElementById('panel-overlay').style.display = 'none';
    setTimeout(() => {
        document.getElementById('visitor-panel').style.display = 'none';
    }, 300);
}

// Real-time WebSocket Updates
document.addEventListener('DOMContentLoaded', function() {
    // Wait for Reverb client to be ready
    setTimeout(function() {
        if (window.reverbClient) {
            // Subscribe to monitoring channel for all visitor updates
            const monitoringChannel = window.reverbClient.subscribe('private-monitoring');
            
            // New visitor joined
            monitoringChannel.bind('visitor.joined', function(data) {
                console.log('New visitor:', data);
                showNotification('New visitor on ' + (data.visitor.client_name || 'site'));
                // Reload to show new visitor (or dynamically add row)
                if ('{{ $tab }}' === 'active') {
                    location.reload();
                }
            });
            
            // Visitor status changed (online/offline)
            monitoringChannel.bind('visitor.status.changed', function(data) {
                console.log('Visitor status changed:', data);
                const row = document.querySelector(`[data-session-id="${data.session_id}"]`);
                if (row) {
                    const statusDot = row.querySelector('td:first-child span');
                    if (statusDot) {
                        if (data.is_online) {
                            statusDot.classList.remove('bg-gray-500');
                            statusDot.classList.add('bg-green-500');
                        } else {
                            statusDot.classList.remove('bg-green-500');
                            statusDot.classList.add('bg-gray-500');
                            // Move to history after a delay
                            if ('{{ $tab }}' === 'active') {
                                setTimeout(() => location.reload(), 2000);
                            }
                        }
                    }
                }
            });
            
            // Visitor changed page
            monitoringChannel.bind('visitor.page.changed', function(data) {
                console.log('Page changed:', data);
                const row = document.querySelector(`[data-session-id="${data.session_id}"]`);
                if (row) {
                    const pageLink = row.querySelector('td:nth-child(4) a');
                    if (pageLink) {
                        pageLink.href = data.page_url;
                        pageLink.textContent = data.page_url.length > 40 
                            ? data.page_url.substring(0, 40) + '...' 
                            : data.page_url;
                    }
                }
            });
            
            console.log('Real-time visitors monitoring active');
        } else {
            console.warn('Reverb client not available. Real-time updates disabled.');
        }
    }, 500);
});

function showNotification(message) {
    // Create toast notification
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 bg-[#fe9e00] text-black px-4 py-3 rounded shadow-lg z-50 animate-pulse';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>
@endpush
@endsection
