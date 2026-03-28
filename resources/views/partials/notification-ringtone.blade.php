{{-- Shared notification + ringtone system — included in all layouts and inbox --}}
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<style>
    #vt-banner { display:none; position:fixed; top:0; left:0; right:0; z-index:9998; background:rgba(34,197,94,0.15); border-bottom:2px solid rgba(34,197,94,0.5); padding:0.75rem 1rem; }
    #vt-banner.active { display:block; }
    #vt-banner.left { background:rgba(107,114,128,0.15); border-bottom-color:rgba(107,114,128,0.4); }
    #vt-toast-container { position:fixed; top:1rem; right:1rem; z-index:9999; display:flex; flex-direction:column; gap:0.75rem; pointer-events:none; max-width:380px; }
    @keyframes vt-ping { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.5)} }
    @keyframes vt-slidein { from{transform:translateX(110%);opacity:0} to{transform:translateX(0);opacity:1} }
    @keyframes vt-slideout { from{transform:translateX(0);opacity:1} to{transform:translateX(110%);opacity:0} }
    .vt-tin { animation:vt-slidein 0.35s ease-out forwards; }
    .vt-tout { animation:vt-slideout 0.3s ease-in forwards; }
</style>

{{-- Banner --}}
<div id="vt-banner">
    <div style="display:flex;align-items:center;justify-content:center;gap:0.75rem;position:relative;max-width:900px;margin:0 auto;">
        <span id="vt-banner-dot" style="display:inline-flex;width:10px;height:10px;border-radius:50%;background:#22c55e;animation:vt-ping 1s infinite;flex-shrink:0;"></span>
        <span id="vt-banner-text" style="font-weight:500;font-size:0.875rem;font-family:Inter,sans-serif;flex:1;text-align:center;color:#4ade80;cursor:pointer;" onclick="vtBannerClick();">New visitor waiting — click to answer</span>
        <button onclick="vtDismiss();" style="position:absolute;right:0;background:none;border:none;cursor:pointer;color:#4ade80;font-size:1.2rem;line-height:1;padding:0 0.25rem;" id="vt-banner-close">✕</button>
    </div>
</div>

{{-- Toast container --}}
<div id="vt-toast-container"></div>

<script>
(function () {
    'use strict';

    // ─── Audio ───────────────────────────────────────────────────────────────
    // Use an <audio> element — unlike Web Audio API, Chrome never auto-suspends it.
    // The ringtone.wav is 30 seconds with loop=true so it rings indefinitely.
    // On first user interaction we "unlock" autoplay by attempting a silent play.
    var _audio = new Audio('/ringtone.wav');
    _audio.loop = true;
    _audio.preload = 'auto';

    var _audioUnlocked = false;
    var _wantRing = false;

    function _unlockAudio() {
        if (_audioUnlocked) return;
        _audioUnlocked = true;
        var prompt = document.getElementById('vt-sound-prompt');
        if (prompt) prompt.remove();
        // If ringing was requested while locked, start now
        if (_wantRing) {
            _audio.currentTime = 0;
            _audio.play().catch(function () {});
        }
    }
    document.addEventListener('click', _unlockAudio, { passive: true });
    document.addEventListener('keydown', _unlockAudio, { passive: true });

    function _startAudio() {
        _wantRing = true;
        if (!_audioUnlocked) {
            _showSoundPrompt();
            return; // _unlockAudio will call play() when the user next interacts
        }
        if (_audio.paused) {
            _audio.currentTime = 0;
            _audio.play().catch(function () { _showSoundPrompt(); });
        }
    }

    function _stopAudio() {
        _wantRing = false;
        _audio.pause();
        _audio.currentTime = 0;
    }

    // Expose vtInitAudio for inbox.blade.php message ping sound
    window.vtInitAudio = function () {
        _unlockAudio();
        if (!window._vtPingCtx) {
            try { window._vtPingCtx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) {}
        }
        return window._vtPingCtx;
    };

    // ─── State ────────────────────────────────────────────────────────────────
    // One simple map: ringKey → { uuid, label }
    // ringKey is either  "s:{sessionId}"  (visitor joined, no chat yet)
    //                 or "c:{chatId}"     (chat created and waiting)
    // This avoids the two-tier system confusion entirely.
    var _rings = {}; // ringKey → { uuid: string|null, label: string }

    // Dismissed chat IDs this tab session (sessionStorage)
    var _dismissed = new Set(JSON.parse(sessionStorage.getItem('vt_dismissed') || '[]'));

    function _saveDismissed() {
        sessionStorage.setItem('vt_dismissed', JSON.stringify([..._dismissed]));
    }

    // chatId → Pusher channel (for chat.closed per-chat events)
    var _chatChannels = {};

    // sessionId → chatId (to link status.changed → chat ring)
    var _sessionToChat = {};

    // chatId → sessionId (reverse map)
    var _chatToSession = {};

    // ─── Ring helpers ─────────────────────────────────────────────────────────

    function _ringCount() {
        return Object.keys(_rings).length;
    }

    function _addRing(key, uuid, label) {
        if (_dismissed.has(key)) return;
        _rings[key] = { uuid: uuid, label: label };
        _updateBanner();
        _startAudio();
    }

    function _removeRing(key) {
        if (!_rings[key]) return;
        delete _rings[key];
        _dismissed.delete(key);
        _saveDismissed();
        _updateBanner();
        if (_ringCount() === 0) _stopAudio();
    }

    function _removeRingByChatId(chatId) {
        var key = 'c:' + chatId;
        _removeRing(key);
        // Also remove session ring for this chat
        var sid = _chatToSession[chatId];
        if (sid) {
            _removeRing('s:' + sid);
            delete _sessionToChat[sid];
            delete _chatToSession[chatId];
        }
    }

    function _subscribeChatChannel(chatId) {
        var key = 'c:' + chatId;
        if (_chatChannels[key]) return;
        var ch = vtPusher.subscribe('chat.' + chatId);
        ch.bind('chat.closed', function (data) {
            _onChatClosed(data.chat_id, data.ended_by);
        });
        _chatChannels[key] = ch;
    }

    function _unsubscribeChatChannel(chatId) {
        var key = 'c:' + chatId;
        if (_chatChannels[key]) {
            try { vtPusher.unsubscribe('chat.' + chatId); } catch (e) {}
            delete _chatChannels[key];
        }
    }

    function _onChatClosed(chatId, endedBy) {
        var wasRinging = !!_rings['c:' + chatId];
        _removeRingByChatId(chatId);
        _unsubscribeChatChannel(chatId);
        if (wasRinging && endedBy === 'visitor') _showVisitorLeft();
    }

    // ─── Banner ───────────────────────────────────────────────────────────────

    function _updateBanner() {
        var banner = document.getElementById('vt-banner');
        var text = document.getElementById('vt-banner-text');
        var dot = document.getElementById('vt-banner-dot');
        var closeBtn = document.getElementById('vt-banner-close');
        if (!banner || !text) return;

        var count = _ringCount();
        if (count === 0) {
            banner.classList.remove('active', 'left');
            return;
        }

        banner.classList.add('active');
        banner.classList.remove('left');
        if (dot) { dot.style.background = '#22c55e'; dot.style.display = 'inline-flex'; }
        if (closeBtn) { closeBtn.style.color = '#4ade80'; closeBtn.style.display = ''; }
        text.style.color = '#4ade80';
        text.style.cursor = 'pointer';

        // Find first chat ring to navigate to
        var firstChatKey = null;
        Object.keys(_rings).forEach(function (k) {
            if (!firstChatKey && k.startsWith('c:')) firstChatKey = k;
        });

        if (count === 1) {
            text.textContent = 'New visitor waiting — click to answer';
        } else {
            text.textContent = count + ' visitors waiting — click to answer';
        }
    }

    function _showVisitorLeft() {
        var banner = document.getElementById('vt-banner');
        var text = document.getElementById('vt-banner-text');
        var dot = document.getElementById('vt-banner-dot');
        var closeBtn = document.getElementById('vt-banner-close');
        if (!banner || !text) return;
        banner.classList.add('active', 'left');
        text.style.color = '#9ca3af';
        text.style.cursor = 'default';
        text.textContent = 'Visitor left the website';
        if (dot) dot.style.display = 'none';
        if (closeBtn) closeBtn.style.display = 'none';
        clearTimeout(window._vtLeftTimer);
        window._vtLeftTimer = setTimeout(function () {
            banner.classList.remove('active', 'left');
        }, 4000);
    }

    function _showSoundPrompt() {
        if (document.getElementById('vt-sound-prompt')) return;
        var el = document.createElement('div');
        el.id = 'vt-sound-prompt';
        el.style.cssText = 'position:fixed;bottom:1.25rem;right:1.25rem;z-index:9999;background:#1a1a1a;border:1px solid #fe9e00;border-radius:0.75rem;padding:0.75rem 1.25rem;font-family:Inter,sans-serif;font-size:0.8rem;color:#fe9e00;cursor:pointer;box-shadow:0 8px 24px rgba(0,0,0,.6);';
        el.textContent = '🔔 Click anywhere to enable ringtone';
        el.onclick = function () { el.remove(); };
        document.body.appendChild(el);
    }

    // ─── Public API ───────────────────────────────────────────────────────────

    window.vtBannerClick = function () {
        // Navigate to first waiting chat
        var firstChatKey = null;
        Object.keys(_rings).forEach(function (k) {
            if (!firstChatKey && k.startsWith('c:')) firstChatKey = k;
        });
        if (firstChatKey) {
            var ring = _rings[firstChatKey];
            window.location.href = '/inbox/' + (ring.uuid || firstChatKey.replace('c:', ''));
            return;
        }
        // Visitor joined but no chat yet — go to live-chat list
        var firstSessionKey = null;
        Object.keys(_rings).forEach(function (k) {
            if (!firstSessionKey && k.startsWith('s:')) firstSessionKey = k;
        });
        if (firstSessionKey) {
            var sid = firstSessionKey.replace('s:', '');
            window.location.href = '/inbox/session/' + sid;
            return;
        }
        window.location.href = '/live-chat';
    };

    window.vtDismiss = function () {
        Object.keys(_rings).forEach(function (k) { _dismissed.add(k); });
        _saveDismissed();
        Object.keys(_rings).forEach(function (k) { delete _rings[k]; });
        _stopAudio();
        _updateBanner();
    };

    // ─── Toast ────────────────────────────────────────────────────────────────

    function _toast(title, body, icon, onClick, duration) {
        duration = duration || 7000;
        var container = document.getElementById('vt-toast-container');
        if (!container) return;
        var t = document.createElement('div');
        t.className = 'vt-tin';
        t.style.cssText = 'pointer-events:auto;cursor:pointer;background:#1a1a1a;border:1px solid #333;border-radius:0.75rem;box-shadow:0 16px 40px rgba(0,0,0,.6);padding:1rem;display:flex;align-items:flex-start;gap:0.75rem;font-family:Inter,sans-serif;';
        t.innerHTML = '<div style="width:38px;height:38px;background:rgba(254,158,0,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><span style="color:#fe9e00;font-weight:700;font-size:0.8rem;">' + (icon || '?') + '</span></div>'
            + '<div style="flex:1;min-width:0;"><p style="font-size:0.875rem;font-weight:600;color:#fff;margin:0;">' + title + '</p><p style="font-size:0.75rem;color:#9ca3af;margin:0.125rem 0 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + body + '</p></div>'
            + '<button style="color:#6b7280;background:none;border:none;cursor:pointer;flex-shrink:0;padding:0;font-size:1rem;" onclick="event.stopPropagation();var p=this.parentElement;p.className=\'vt-tout\';setTimeout(function(){p.remove();},300);">✕</button>';
        if (onClick) {
            t.addEventListener('click', function () { onClick(); t.className = 'vt-tout'; setTimeout(function () { t.remove(); }, 300); });
        }
        container.appendChild(t);
        setTimeout(function () { if (t.parentNode) { t.className = 'vt-tout'; setTimeout(function () { t.remove(); }, 300); } }, duration);
    }

    // ─── Pusher ───────────────────────────────────────────────────────────────

    var vtPusher = new Pusher('{{ config('reverb.apps.apps.0.key') }}', {
        wsHost: '{{ config('broadcasting.connections.reverb.options.host', '127.0.0.1') }}',
        wsPort: {{ config('broadcasting.connections.reverb.options.port', 8080) }},
        wssPort: {{ config('broadcasting.connections.reverb.options.port', 8080) }},
        forceTLS: false,
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        cluster: 'mt1',
        authEndpoint: '/broadcasting/auth',
        auth: { headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '' } },
    });

    window.vtPusher = vtPusher;
    var monitoringChannel = vtPusher.subscribe('monitoring');
    window.vtMonitoringChannel = monitoringChannel;

    // ─── Page-load hydration ──────────────────────────────────────────────────
    // Restore ringing state from server for any chats still waiting in DB.
    // $waitingChats only includes chats where visitor is still online (AppServiceProvider).

    (function () {
        var waiting = @json(($waitingChats ?? collect())->map(fn($c) => ['id' => $c->id, 'uuid' => $c->uuid, 'session_id' => $c->visitor_session_id])->values());

        // Prune dismissed keys that are no longer relevant
        var validKeys = new Set();
        waiting.forEach(function (c) { validKeys.add('c:' + c.id); });
        _dismissed = new Set([..._dismissed].filter(function (k) { return validKeys.has(k); }));
        _saveDismissed();

        waiting.forEach(function (c) {
            if (c.session_id) {
                _sessionToChat[c.session_id] = c.id;
                _chatToSession[c.id] = c.session_id;
            }
            var key = 'c:' + c.id;
            if (!_dismissed.has(key)) {
                _addRing(key, c.uuid, 'Chat #' + c.id);
                _subscribeChatChannel(c.id);
            }
        });
    }());

    // ─── Event handlers ───────────────────────────────────────────────────────

    // Visitor opens the widget — ring immediately
    monitoringChannel.bind('visitor.joined', function (data) {
        var name = (data.visitor && data.visitor.name) ? data.visitor.name : 'Anonymous';
        var country = (data.visitor && data.visitor.location && data.visitor.location.country) ? data.visitor.location.country : '';
        var sid = data.session && data.session.id;

        if (sid) {
            _addRing('s:' + sid, null, name);
        }

        _toast('New Visitor 🔔', 'New visitor from ' + (country || 'Unknown'), name.substring(0, 2).toUpperCase(),
            sid ? function () { window.location.href = '/inbox/session/' + sid; } : null);

        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('New Visitor', { body: name + (country ? ' from ' + country : ''), icon: '/favicon.ico', tag: 'vj-' + (sid || Date.now()), silent: true });
        }
    });

    // Visitor sent first message — chat created and waiting
    monitoringChannel.bind('visitor.updated', function (data) {
        if (!data.chat || data.chat.status !== 'waiting') return;

        var chatId = data.chat.id;
        var sid = data.chat.visitor_session_id;
        var name = (data.visitor && data.visitor.name) ? data.visitor.name : 'Anonymous';
        var uuid = data.chat.uuid || null;
        var chatUrl = '/inbox/' + (uuid || chatId);

        // Swap session ring → chat ring WITHOUT stopping audio.
        // We delete the 's:' key directly (bypassing _removeRing's audio-stop logic)
        // then immediately add the 'c:' key so _ringCount never hits 0 during the swap.
        if (sid) {
            delete _rings['s:' + sid]; // silent delete — keeps audio running
            _sessionToChat[sid] = chatId;
            _chatToSession[chatId] = sid;
        }

        _addRing('c:' + chatId, uuid, name);
        _subscribeChatChannel(chatId);

        _toast('New Chat Waiting', name + ' is waiting for an agent', name.substring(0, 2).toUpperCase(),
            function () { window.location.href = chatUrl; }, 12000);
    });

    // An agent joined — stop ringing everywhere, notify others
    monitoringChannel.bind('agent.joined', function (data) {
        if (!data.chat_id) return;

        var agentName = (data.agent && data.agent.name) ? data.agent.name : 'An agent';
        var chatUrl = '/inbox/' + (data.chat_uuid || data.chat_id);

        _onChatClosed(data.chat_id, 'agent'); // reuse — stops ring, cleans up

        _toast('Chat Picked Up', agentName + ' joined the chat', agentName.substring(0, 2).toUpperCase(),
            function () { window.location.href = chatUrl; }, 8000);
    });

    // Chat explicitly closed (agent ended or visitor left while chatting)
    monitoringChannel.bind('chat.closed', function (data) {
        if (!data.chat_id) return;
        _onChatClosed(data.chat_id, data.ended_by);
    });

    // Visitor closed their browser tab / window
    monitoringChannel.bind('status.changed', function (data) {
        if (data.is_online || !data.session_id) return;

        var sid = data.session_id;
        var hadSessionRing = !!_rings['s:' + sid];
        var chatId = _sessionToChat[sid];

        _removeRing('s:' + sid);

        if (chatId) {
            var hadChatRing = !!_rings['c:' + chatId];
            _removeRingByChatId(chatId);
            _unsubscribeChatChannel(chatId);
            if (hadChatRing || hadSessionRing) _showVisitorLeft();
        } else if (hadSessionRing) {
            _showVisitorLeft();
        }
    });

    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // Also expose vtStartRinging / vtStopRinging for any legacy callers
    window.vtStartRinging = function (chatId) { _addRing('c:' + chatId, null, 'Chat #' + chatId); };
    window.vtStopRinging = function (chatId) { _removeRingByChatId(chatId); };

}());
</script>
