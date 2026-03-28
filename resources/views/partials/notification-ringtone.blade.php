{{-- Shared notification + ringtone system for all dashboard pages --}}
{{-- Provides: real-time new chat alerts with looping ringtone --}}
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<style>
    @keyframes slideIn { from { transform: translateX(110%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(110%); opacity: 0; } }
    .vt-toast-in { animation: slideIn 0.35s ease-out forwards; }
    .vt-toast-out { animation: slideOut 0.3s ease-in forwards; }
    #vt-ringing-banner { display: none; }
    #vt-ringing-banner.active { display: block; }
    @keyframes ping { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.4)} }
</style>

{{-- Hidden ringtone audio element — loop attribute handles continuous ringing --}}
{{-- Using <audio> instead of Web Audio API: browsers allow autoplay for <audio> after --}}
{{-- any prior user interaction with the page (navigating, clicking a menu, etc.) --}}
<audio id="vt-ringtone-audio" src="/ringtone.wav" loop preload="auto" style="display:none;"></audio>

{{-- Toast container --}}
<div id="vt-toast-container" style="position:fixed;top:1rem;right:1rem;z-index:9999;display:flex;flex-direction:column;gap:0.75rem;pointer-events:none;max-width:380px;"></div>

{{-- Ringing banner — click main area goes to chat, × button dismisses --}}
<div id="vt-ringing-banner"
     style="position:fixed;top:0;left:0;right:0;z-index:9998;background:rgba(34,197,94,0.15);border-bottom:1px solid rgba(34,197,94,0.4);padding:0.75rem 1rem;">
    <div style="display:flex;align-items:center;justify-content:center;gap:0.75rem;position:relative;">
        <span style="display:inline-flex;width:12px;height:12px;border-radius:50%;background:#22c55e;animation:ping 1s infinite;flex-shrink:0;"></span>
        <span id="vt-ringing-text" onclick="vtGoToWaitingChat();" style="color:#4ade80;font-weight:500;font-size:0.875rem;font-family:Inter,sans-serif;cursor:pointer;flex:1;text-align:center;">New chat waiting — click to answer</span>
        <span style="display:inline-flex;width:12px;height:12px;border-radius:50%;background:#22c55e;animation:ping 1s infinite;flex-shrink:0;"></span>
        <button onclick="vtDismissBanner();" title="Dismiss" style="position:absolute;right:0;background:none;border:none;cursor:pointer;color:#4ade80;font-size:1.1rem;line-height:1;padding:0 0.25rem;">✕</button>
    </div>
</div>

<script>
(function() {
    'use strict';

    // ---------------------------------------------------------------------------
    // Audio — uses <audio> element which has more permissive autoplay than Web Audio API.
    // Browsers allow <audio>.play() without a gesture if the user has previously
    // interacted with the page (navigation counts). This means agents away from
    // keyboard will still hear the ringtone when a new chat arrives.
    // ---------------------------------------------------------------------------
    const vtAudioEl = document.getElementById('vt-ringtone-audio');
    let vtAudioUnlocked = false;

    // Unlock audio on first interaction so subsequent .play() calls succeed
    function vtUnlockAudio() {
        if (vtAudioUnlocked) return;
        vtAudioUnlocked = true;
        // Play and immediately pause to satisfy autoplay policy
        vtAudioEl.play().then(function() {
            vtAudioEl.pause();
            vtAudioEl.currentTime = 0;
        }).catch(function() {});
    }

    document.addEventListener('click', vtUnlockAudio, { once: false });
    document.addEventListener('keydown', vtUnlockAudio, { once: false });

    // vtInitAudio kept on window for backward compatibility (inbox uses it for message ping sound)
    window.vtInitAudio = function() {
        vtUnlockAudio();
        // Return a minimal AudioContext-like object for the message ping in inbox.blade.php
        if (!window._vtMsgAudioCtx) {
            try {
                window._vtMsgAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
            } catch (e) {}
        }
        return window._vtMsgAudioCtx;
    };

    function vtPlayRingtone() {
        vtAudioEl.currentTime = 0;
        vtAudioEl.play().catch(function() {
            // Autoplay blocked — show a prompt and retry on next interaction
            vtShowClickToEnableSound();
            var retryOnInteraction = function() {
                if (!vtAudioEl.paused) return; // Already playing from another handler
                vtAudioEl.play().catch(function() {});
                document.removeEventListener('click', retryOnInteraction);
                document.removeEventListener('keydown', retryOnInteraction);
                var prompt = document.getElementById('vt-sound-prompt');
                if (prompt) { prompt.style.display = 'none'; }
            };
            document.addEventListener('click', retryOnInteraction);
            document.addEventListener('keydown', retryOnInteraction);
        });
    }

    function vtShowClickToEnableSound() {
        var existing = document.getElementById('vt-sound-prompt');
        if (existing) return;
        var el = document.createElement('div');
        el.id = 'vt-sound-prompt';
        el.style.cssText = 'position:fixed;bottom:1rem;right:1rem;z-index:9999;background:#1a1a1a;border:1px solid #fe9e00;border-radius:0.75rem;padding:0.75rem 1rem;font-family:Inter,sans-serif;font-size:0.8rem;color:#fe9e00;cursor:pointer;box-shadow:0 8px 24px rgba(0,0,0,.5);';
        el.textContent = '🔔 Click anywhere to enable ringtone';
        el.onclick = function() { el.style.display = 'none'; };
        document.body.appendChild(el);
        // Auto-hide after audio unlocks
        setTimeout(function() {
            if (el.parentNode && vtAudioUnlocked) { el.style.display = 'none'; }
        }, 8000);
    }

    function vtStopRingtone() {
        vtAudioEl.pause();
        vtAudioEl.currentTime = 0;
    }

    // ---------------------------------------------------------------------------
    // State
    // ---------------------------------------------------------------------------

    // Set of numeric chat IDs currently ringing (visitor sent message, chat is waiting)
    const vtRingingChats = new Set();

    // sessionId → true for visitors who joined but haven't sent a message yet
    // Cleared when visitor sends a message (visitor.updated) or goes offline (status.changed)
    const vtSessionRing = {};

    // Dismissed chat IDs for this browser tab — survives soft navigation, resets on new tab
    let vtDismissedChats = new Set(
        JSON.parse(sessionStorage.getItem('vt_dismissed_chats') || '[]')
    );

    // visitorSessionId (string) → chatId (number)
    const vtSessionChatMap = {};

    // chatId (number) → uuid (string)
    const vtChatUuidMap = {};

    // chatId (string key) → Pusher channel object
    const vtChatChannels = {};

    // Are there any active rings at all (either session-level or chat-level)?
    function vtIsRinging() {
        return vtRingingChats.size > 0 || Object.keys(vtSessionRing).length > 0;
    }

    // ---------------------------------------------------------------------------
    // Core helpers
    // ---------------------------------------------------------------------------

    function vtNormalizeId(chatId) {
        const n = Number(chatId);
        return isNaN(n) ? chatId : n;
    }

    function vtSaveSessionStorage() {
        sessionStorage.setItem('vt_dismissed_chats', JSON.stringify([...vtDismissedChats]));
    }

    // Central cleanup for a chat that has ended or been picked up
    // endedBy: 'visitor' | 'agent' | null
    function vtCloseChat(chatId, endedBy) {
        const numericId = vtNormalizeId(chatId);
        const wasRinging = vtRingingChats.has(numericId);

        vtRingingChats.delete(numericId);

        // Reverse-lookup session map and clean it up
        for (const [sid, cId] of Object.entries(vtSessionChatMap)) {
            if (vtNormalizeId(cId) === numericId) {
                delete vtSessionChatMap[sid];
                break;
            }
        }

        // Clean uuid map
        delete vtChatUuidMap[numericId];

        // Unsubscribe from per-chat channel
        const key = String(numericId);
        if (vtChatChannels[key]) {
            try { vtPusher.unsubscribe('chat.' + numericId); } catch (e) {}
            delete vtChatChannels[key];
        }

        // Remove from dismissed — chat is done, no need to keep it suppressed
        vtDismissedChats.delete(String(numericId));
        vtSaveSessionStorage();

        if (!vtIsRinging()) {
            vtStopRingtone();
        }
        vtUpdateBanner();

        if (wasRinging && endedBy === 'visitor') {
            vtShowVisitorLeft();
        }
    }

    function vtSubscribeChatChannel(chatId) {
        const key = String(chatId);
        if (vtChatChannels[key]) return;
        const ch = vtPusher.subscribe('chat.' + chatId);
        ch.bind('chat.closed', function(data) {
            vtCloseChat(data.chat_id, data.ended_by);
        });
        vtChatChannels[key] = ch;
    }

    // ---------------------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------------------

    window.vtStartRinging = function(chatId) {
        const id = vtNormalizeId(chatId);
        if (vtDismissedChats.has(String(id))) return;

        vtRingingChats.add(id);
        vtUpdateBanner();

        vtSubscribeChatChannel(id);

        // Only start audio if not already playing
        if (vtAudioEl.paused) {
            vtPlayRingtone();
        }
    };

    window.vtStopRinging = function(chatId) {
        if (chatId !== undefined) {
            vtRingingChats.delete(vtNormalizeId(chatId));
        } else {
            vtRingingChats.clear();
        }
        if (!vtIsRinging()) {
            vtStopRingtone();
        }
        vtUpdateBanner();
    };

    window.vtDismissBanner = function() {
        vtRingingChats.forEach(function(id) { vtDismissedChats.add(String(id)); });
        vtSaveSessionStorage();
        vtRingingChats.clear();
        // Clear session-level rings too
        Object.keys(vtSessionRing).forEach(function(sid) { delete vtSessionRing[sid]; });
        vtStopRingtone();
        vtUpdateBanner();
    };

    window.vtGoToWaitingChat = function() {
        for (const id of vtRingingChats) {
            const uuid = vtChatUuidMap[id] || id;
            window.location.href = '/inbox/' + uuid;
            return;
        }
        window.location.href = '/live-chat';
    };

    // ---------------------------------------------------------------------------
    // UI
    // ---------------------------------------------------------------------------

    function vtUpdateBanner() {
        const banner = document.getElementById('vt-ringing-banner');
        const text = document.getElementById('vt-ringing-text');
        if (!banner || !text) return;

        const chatCount = vtRingingChats.size;
        const sessionCount = Object.keys(vtSessionRing).length;
        const totalCount = chatCount + sessionCount;

        if (totalCount > 0) {
            banner.style.background = 'rgba(34,197,94,0.15)';
            banner.style.borderBottomColor = 'rgba(34,197,94,0.4)';
            text.style.color = '#4ade80';
            text.style.cursor = chatCount > 0 ? 'pointer' : 'default';
            text.onclick = chatCount > 0 ? vtGoToWaitingChat : null;
            text.textContent = totalCount === 1
                ? '1 new visitor waiting — click to answer'
                : totalCount + ' visitors waiting — click to answer';
            banner.classList.add('active');
        } else {
            banner.classList.remove('active');
        }
    }

    function vtShowVisitorLeft() {
        const banner = document.getElementById('vt-ringing-banner');
        const text = document.getElementById('vt-ringing-text');
        if (!banner || !text) return;
        banner.style.background = 'rgba(107,114,128,0.15)';
        banner.style.borderBottomColor = 'rgba(107,114,128,0.4)';
        text.style.color = '#9ca3af';
        text.style.cursor = 'default';
        text.onclick = null;
        text.textContent = 'Visitor left the website';
        banner.classList.add('active');
        clearTimeout(window._vtLeftTimeout);
        window._vtLeftTimeout = setTimeout(function() {
            banner.classList.remove('active');
        }, 4000);
    }

    function vtShowToast({ title, body, icon, onClick, duration = 6000 }) {
        const container = document.getElementById('vt-toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = 'vt-toast-in';
        toast.style.cssText = 'pointer-events:auto;cursor:pointer;background:#1a1a1a;border:1px solid #333;border-radius:0.75rem;box-shadow:0 20px 40px rgba(0,0,0,.5);padding:1rem;display:flex;align-items:flex-start;gap:0.75rem;font-family:Inter,sans-serif;';
        toast.innerHTML =
            '<div style="width:40px;height:40px;background:rgba(254,158,0,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
                '<span style="color:#fe9e00;font-weight:600;font-size:0.875rem;">' + (icon || '?') + '</span>' +
            '</div>' +
            '<div style="flex:1;min-width:0;">' +
                '<p style="font-size:0.875rem;font-weight:600;color:#fff;margin:0;">' + title + '</p>' +
                '<p style="font-size:0.75rem;color:#9ca3af;margin:0.125rem 0 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + body + '</p>' +
            '</div>' +
            '<button style="color:#6b7280;background:none;border:none;cursor:pointer;flex-shrink:0;padding:0;margin-top:2px;font-size:1rem;line-height:1;" onclick="event.stopPropagation();var t=this.closest(\'.vt-toast-in, .vt-toast-out\') || this.parentElement.parentElement;t.classList.add(\'vt-toast-out\');setTimeout(function(){t.remove();},300);">✕</button>';

        if (onClick) {
            toast.addEventListener('click', function() {
                onClick();
                toast.classList.add('vt-toast-out');
                setTimeout(function() { toast.remove(); }, 300);
            });
        }
        container.appendChild(toast);
        setTimeout(function() {
            if (toast.parentNode) {
                toast.classList.add('vt-toast-out');
                setTimeout(function() { toast.remove(); }, 300);
            }
        }, duration);
    }

    // ---------------------------------------------------------------------------
    // Pusher — single shared connection
    // ---------------------------------------------------------------------------

    const vtPusher = new Pusher('{{ config('reverb.apps.apps.0.key') }}', {
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
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
            },
        },
    });

    window.vtPusher = vtPusher;

    const monitoringChannel = vtPusher.subscribe('monitoring');
    window.vtMonitoringChannel = monitoringChannel;

    // ---------------------------------------------------------------------------
    // Page-load hydration — restore ringing for any chats still waiting in DB
    // ---------------------------------------------------------------------------
    (function() {
        const waitingChats = @json(($waitingChats ?? collect())->map(fn($c) => ['id' => $c->id, 'uuid' => $c->uuid, 'session_id' => $c->visitor_session_id])->values());

        // Prune stale dismissed IDs — only keep ones that are actually still waiting
        const currentWaitingIds = new Set(waitingChats.map(function(c) { return String(c.id); }));
        vtDismissedChats = new Set([...vtDismissedChats].filter(function(id) {
            return currentWaitingIds.has(id);
        }));
        vtSaveSessionStorage();

        waitingChats.forEach(function(chat) {
            if (chat.session_id) {
                vtSessionChatMap[chat.session_id] = chat.id;
            }
            if (chat.uuid) {
                vtChatUuidMap[chat.id] = chat.uuid;
            }
            if (!vtDismissedChats.has(String(chat.id))) {
                vtStartRinging(chat.id);
            }
        });
    })();

    // ---------------------------------------------------------------------------
    // Monitoring channel event handlers
    // ---------------------------------------------------------------------------

    // visitor.joined — start ringing immediately when a visitor opens the chat widget
    monitoringChannel.bind('visitor.joined', function(data) {
        const visitorName = (data.visitor && data.visitor.name) ? data.visitor.name : 'Anonymous';
        const country = (data.visitor && data.visitor.location && data.visitor.location.country) ? data.visitor.location.country : 'Unknown';
        const sessionId = data.session && data.session.id;

        // Ring using session ID as key — replaced by numeric chatId when visitor.updated fires
        if (sessionId) {
            vtSessionRing[sessionId] = true;
            if (vtAudioEl.paused) {
                vtPlayRingtone();
            }
            vtUpdateBanner();
        }

        vtShowToast({
            title: 'New Visitor',
            body: visitorName + ' from ' + country,
            icon: visitorName.substring(0, 2).toUpperCase(),
            onClick: sessionId ? function() { window.location.href = '/inbox/session/' + sessionId; } : null,
        });

        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('New Visitor', {
                body: visitorName + ' from ' + country,
                icon: '/favicon.ico',
                tag: 'new-visitor-' + (sessionId || Date.now()),
            });
        }
    });

    // visitor.updated — visitor sent a message and chat is now waiting
    monitoringChannel.bind('visitor.updated', function(data) {
        if (!data.chat || data.chat.status !== 'waiting') return;

        const chatId = data.chat.id;
        const sessionId = data.chat.visitor_session_id;
        const visitorName = (data.visitor && data.visitor.name) ? data.visitor.name : 'Anonymous';
        const chatUrl = '/inbox/' + (data.chat.uuid || chatId);

        // Clear session-level ring (visitor has now sent a message, chat-level ring takes over)
        if (sessionId && vtSessionRing[sessionId]) {
            delete vtSessionRing[sessionId];
        }

        // If this session was previously mapped to a different (old) chat, close that silently
        if (sessionId) {
            const oldChatId = vtSessionChatMap[sessionId];
            if (oldChatId && vtNormalizeId(oldChatId) !== vtNormalizeId(chatId)) {
                vtCloseChat(oldChatId, null);
            }
            vtSessionChatMap[sessionId] = chatId;
        }

        if (data.chat.uuid) {
            vtChatUuidMap[chatId] = data.chat.uuid;
        }

        // Ensure this chat isn't suppressed from a previous dismissal
        vtDismissedChats.delete(String(chatId));
        vtSaveSessionStorage();

        vtStartRinging(chatId);

        vtShowToast({
            title: 'New Chat Waiting',
            body: visitorName + ' is waiting for an agent',
            icon: visitorName.substring(0, 2).toUpperCase(),
            onClick: function() { window.location.href = chatUrl; },
            duration: 10000,
        });
    });

    // agent.joined — someone picked up the chat, stop ringing for everyone
    monitoringChannel.bind('agent.joined', function(data) {
        if (!data.chat_id) return;

        vtCloseChat(data.chat_id, 'agent');

        const agentName = (data.agent && data.agent.name) ? data.agent.name : 'An agent';
        const chatUrl = '/inbox/' + (data.chat_uuid || data.chat_id);

        vtShowToast({
            title: 'Chat Picked Up',
            body: agentName + ' joined the chat',
            icon: agentName.substring(0, 2).toUpperCase(),
            onClick: function() { window.location.href = chatUrl; },
            duration: 8000,
        });
    });

    // chat.closed — visitor left or agent ended chat
    monitoringChannel.bind('chat.closed', function(data) {
        if (!data.chat_id) return;
        vtCloseChat(data.chat_id, data.ended_by);
    });

    // status.changed — visitor went offline (closed tab/window)
    monitoringChannel.bind('status.changed', function(data) {
        if (data.is_online || !data.session_id) return;

        const wasRinging = vtIsRinging();

        // Clear session-level ring (visitor left before sending a message)
        if (vtSessionRing[data.session_id]) {
            delete vtSessionRing[data.session_id];
        }

        // Clear chat-level ring if visitor had started a chat
        const chatId = vtSessionChatMap[data.session_id];
        if (chatId) {
            vtCloseChat(chatId, 'visitor');
        } else {
            // No chat was started — just stop audio if nothing else is ringing
            if (!vtIsRinging()) {
                vtStopRingtone();
            }
            vtUpdateBanner();
            if (wasRinging) {
                vtShowVisitorLeft();
            }
        }
    });

    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
})();
</script>
