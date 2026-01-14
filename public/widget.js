(function() {
    'use strict';

    // Configuration
    const config = {
        apiUrl: window.LIVE_CHAT_API_URL || '/api',
        wsUrl: window.LIVE_CHAT_WS_URL || 'ws://localhost:8080',
        wsKey: window.LIVE_CHAT_WS_KEY || null,
        wsHost: window.LIVE_CHAT_WS_HOST || window.location.hostname,
        wsPort: window.LIVE_CHAT_WS_PORT || 8080,
        widgetKey: null,
        visitorKey: null,
        sessionKey: null,
        // Client widget settings (loaded from API)
        widgetColor: '#fe9e00',
        widgetIcon: 'chat',
        widgetIconUrl: null,
        widgetPosition: 'bottom-right',
        welcomeTitle: 'Hi there! 👋',
        welcomeMessage: 'How can we help you today?',
        agentName: 'Support Team',
        agentAvatar: null,
        showBranding: true,
        autoOpen: false,
        autoOpenDelay: 5,
    };

    // State
    let state = {
        isOpen: false,
        isMinimized: false,
        chatId: null,
        messages: [],
        isTyping: false,
        pusher: null,
        channel: null,
        hasSubmittedDetails: false,
        visitorDetails: null,
        unreadCount: 0,
        originalTitle: document.title, // Store original title for tab badge
    };
    
    // Get list of commonly used emojis
    function getEmojiList() {
        return [
            '😊', '😂', '😍', '🥰', '😘', '🤗', '😎', '🤔',
            '😅', '😉', '🙂', '😇', '👍', '👏', '🙏', '💪',
            '❤️', '💯', '✨', '🎉', '👋', '🤝', '👀', '💡',
            '✅', '❌', '⭐', '🔥', '💬', '📞', '📧', '🕐'
        ];
    }
    function loadPusherLibrary(callback) {
        if (window.Pusher) {
            callback();
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://js.pusher.com/8.2.0/pusher.min.js';
        script.onload = callback;
        script.onerror = () => console.error('Failed to load Pusher library');
        document.head.appendChild(script);
    }

    // Initialize widget
    function init(widgetKey) {
        config.widgetKey = widgetKey;

        // Load widget configuration
        fetch(`${config.apiUrl}/widget/config?widget_key=${widgetKey}`)
            .then(res => res.json())
            .then(data => {
                config.apiUrl = data.api_url || config.apiUrl;
                config.wsKey = data.ws_key;
                config.wsHost = data.ws_host || '127.0.0.1';
                config.wsPort = data.ws_port || 8080;
                config.wsScheme = data.ws_scheme || 'http';
                
                // Load client widget settings
                config.widgetColor = data.widget_color || '#fe9e00';
                config.widgetIcon = data.widget_icon || 'chat';
                config.widgetIconUrl = data.widget_icon_url || null;
                config.widgetPosition = data.widget_position || 'bottom-right';
                config.welcomeTitle = data.widget_welcome_title || 'Hi there! 👋';
                config.welcomeMessage = data.widget_welcome_message || 'How can we help you today?';
                config.agentName = data.widget_agent_name || 'Support Team';
                config.agentAvatar = data.widget_agent_avatar || null;
                config.showBranding = data.widget_show_branding !== false;
                config.autoOpen = data.widget_auto_open || false;
                config.autoOpenDelay = data.widget_auto_open_delay || 5;

                // Get or create visitor key from cookie
                config.visitorKey = getCookie('live_chat_visitor_key');
                if (!config.visitorKey) {
                    config.visitorKey = generateUUID();
                    setCookie('live_chat_visitor_key', config.visitorKey, 365);
                }

                // Create widget HTML
                createWidget();

                // Track initial page visit
                trackPage();

                // Listen for page changes
                trackPageChanges();

                // Load Pusher first, then load saved details (which may init WebSocket)
                loadPusherLibrary(() => {
                    console.log('Pusher library loaded');
                    // Now load saved visitor details (may call initChatWebSocket)
                    loadSavedVisitorDetails();
                    // Check if agent initiated a chat before visitor filled form
                    checkExistingChat();
                    // Also init WebSocket for general listening
                    initWebSocket();
                    // Subscribe to visitor channel for proactive messages
                    subscribeToVisitorChannel();
                });
                
                // Auto-open widget after delay
                if (config.autoOpen) {
                    setTimeout(() => {
                        if (!state.isOpen && !getCookie('live_chat_auto_opened')) {
                            state.isOpen = true;
                            document.getElementById('live-chat-window').classList.add('open');
                            setCookie('live_chat_auto_opened', '1', 1); // Don't auto-open again today
                        }
                    }, config.autoOpenDelay * 1000);
                }
            })
            .catch(err => {
                console.error('Failed to load widget config:', err);
                // Continue with defaults
                config.visitorKey = getCookie('live_chat_visitor_key') || generateUUID();
                setCookie('live_chat_visitor_key', config.visitorKey, 365);
                createWidget();
                trackPage();
                trackPageChanges();
            });
    }

    // Create widget HTML
    function createWidget() {
        // Determine icon SVG based on widget_icon setting
        const iconSvg = getIconSvg(config.widgetIcon);
        
        const widget = document.createElement('div');
        widget.id = 'live-chat-widget';
        widget.className = config.widgetPosition === 'bottom-left' ? 'position-left' : '';
        widget.innerHTML = `
            <!-- Proactive Message Bubble -->
            <div class="proactive-bubble" id="proactive-bubble" style="display: none;">
                <div class="proactive-content">
                    <div class="proactive-avatar" id="proactive-avatar"></div>
                    <div class="proactive-text">
                        <strong id="proactive-agent-name"></strong>
                        <p id="proactive-message-text"></p>
                    </div>
                    <button class="proactive-close" onclick="window.LiveChatWidget.closeProactiveBubble()">×</button>
                </div>
            </div>
            <div class="live-chat-button" onclick="window.LiveChatWidget.toggle()">
                ${config.widgetIconUrl ? `<img src="${config.widgetIconUrl}" width="28" height="28" alt="Chat">` : iconSvg}
            </div>
            <div class="live-chat-window" id="live-chat-window">
                <div class="live-chat-header">
                    <h3>${config.welcomeTitle}</h3>
                    <div class="live-chat-actions">
                        <button onclick="window.LiveChatWidget.minimize()">−</button>
                        <button onclick="window.LiveChatWidget.close()">×</button>
                    </div>
                </div>
                <div class="live-chat-body" id="live-chat-body">
                    <!-- Messages area (shown by default) -->
                    <div id="live-chat-messages" class="live-chat-messages">
                        <div class="messages-container" id="messages-container">
                            <div class="welcome-message">
                                <div class="agent-info">
                                    ${config.agentAvatar ? `<img src="${config.agentAvatar}" class="agent-avatar">` : `<div class="agent-avatar-placeholder">${config.agentName.charAt(0)}</div>`}
                                    <strong>${config.agentName}</strong>
                                </div>
                                <p>${config.welcomeMessage}</p>
                            </div>
                        </div>
                        <div class="typing-indicator" id="typing-indicator" style="display: none;">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
                <div class="live-chat-footer" id="live-chat-footer">
                    <div class="emoji-picker-container" style="position: relative;">
                        <button type="button" id="emoji-picker-btn" class="emoji-btn">😊</button>
                        <div id="emoji-picker" class="emoji-picker" style="display: none;">
                            <div class="emoji-grid">
                                ${getEmojiList().map(e => `<span class="emoji-item" data-emoji="${e}">${e}</span>`).join('')}
                            </div>
                        </div>
                    </div>
                    <input type="text" id="message-input" placeholder="Type a message...">
                    <button id="file-upload-btn">📎</button>
                    <button id="send-btn" onclick="window.LiveChatWidget.sendMessage()">Send</button>
                    <input type="file" id="file-input" style="display: none;" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
                <!-- Details form (hidden, overlays full widget on first message) -->
                <div id="live-chat-details-form" class="live-chat-details-form" style="display: none;">
                    <h4>Before we chat, please share your details:</h4>
                    <form id="details-form">
                        <input type="text" name="name" placeholder="Your Name *" required>
                        <input type="email" name="email" placeholder="Email *" required>
                        <input type="tel" name="phone" placeholder="Phone">
                        <input type="hidden" name="pending_message" id="pending-message">
                        <button type="submit">Send Message</button>
                    </form>
                </div>
            </div>
        `;
        document.body.appendChild(widget);
        injectStyles();
        attachEventListeners();
    }

    // Inject CSS styles
    function injectStyles() {
        const style = document.createElement('style');
        style.textContent = `
            #live-chat-widget {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 9999;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            #live-chat-widget.position-left {
                right: auto;
                left: 20px;
            }
            #live-chat-widget.position-left .live-chat-window {
                right: auto;
                left: 0;
            }
            #live-chat-widget.position-left .proactive-bubble {
                right: auto;
                left: 0;
            }
            .live-chat-button {
                width: 60px;
                height: 60px;
                background: ${config.widgetColor};
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transition: transform 0.2s;
            }
            .live-chat-button:hover {
                transform: scale(1.1);
            }
            .live-chat-button img {
                border-radius: 50%;
                object-fit: cover;
            }
            /* Proactive Message Bubble */
            .proactive-bubble {
                position: absolute;
                bottom: 80px;
                right: 0;
                width: 320px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 5px 40px rgba(0,0,0,0.16);
                animation: slideUp 0.3s ease;
                cursor: pointer;
            }
            .proactive-bubble:hover {
                box-shadow: 0 8px 50px rgba(0,0,0,0.2);
            }
            @keyframes slideUp {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .proactive-content {
                display: flex;
                align-items: flex-start;
                padding: 16px;
                gap: 12px;
            }
            .proactive-avatar {
                width: 40px;
                height: 40px;
                background: ${config.widgetColor};
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: bold;
                flex-shrink: 0;
                font-size: 14px;
            }
            .proactive-avatar img {
                width: 100%;
                height: 100%;
                border-radius: 50%;
                object-fit: cover;
            }
            .proactive-text {
                flex: 1;
                min-width: 0;
            }
            .proactive-text strong {
                display: block;
                font-size: 14px;
                color: #333;
                margin-bottom: 4px;
            }
            .proactive-text p {
                margin: 0;
                font-size: 14px;
                color: #555;
                line-height: 1.4;
            }
            .proactive-close {
                position: absolute;
                top: 8px;
                right: 8px;
                background: none;
                border: none;
                font-size: 18px;
                color: #999;
                cursor: pointer;
                padding: 0;
                line-height: 1;
            }
            .proactive-close:hover {
                color: #333;
            }
            /* Welcome message agent info */
            .agent-info {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 8px;
            }
            .agent-avatar, .agent-avatar-placeholder {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                object-fit: cover;
            }
            .agent-avatar-placeholder {
                background: ${config.widgetColor};
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-size: 14px;
            }
            .live-chat-window {
                position: absolute;
                bottom: 80px;
                right: 0;
                width: 380px;
                height: 600px;
                background: white;
                border-radius: 16px;
                box-shadow: 0 12px 48px rgba(0,0,0,0.25), 0 4px 12px rgba(0,0,0,0.1);
                display: flex;
                flex-direction: column;
                opacity: 0;
                visibility: hidden;
                transform: translateY(20px) scale(0.95);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                overflow: hidden;
            }
            .live-chat-window.open {
                opacity: 1;
                visibility: visible;
                transform: translateY(0) scale(1);
            }
            
            /* Mobile responsive styles */
            @media (max-width: 480px) {
                #live-chat-widget {
                    bottom: 10px !important;
                    right: 10px !important;
                }
                #live-chat-widget.position-left {
                    left: 10px !important;
                }
                .live-chat-button {
                    width: 56px;
                    height: 56px;
                }
                .live-chat-window {
                    position: fixed !important;
                    bottom: 0 !important;
                    right: 0 !important;
                    left: 0 !important;
                    width: 100% !important;
                    height: calc(100vh - 60px) !important;
                    max-height: calc(100vh - 60px) !important;
                    border-radius: 16px 16px 0 0 !important;
                    transform: translateY(100%);
                }
                .live-chat-window.open {
                    transform: translateY(0);
                }
                .proactive-bubble {
                    width: calc(100vw - 80px) !important;
                    max-width: 300px;
                    right: 10px !important;
                }
                .live-chat-footer {
                    padding: 10px 12px;
                    padding-bottom: max(10px, env(safe-area-inset-bottom));
                }
                .live-chat-footer input {
                    font-size: 16px; /* Prevent iOS zoom */
                }
            }
            .live-chat-header {
                background: linear-gradient(135deg, ${config.widgetColor} 0%, ${config.widgetColor}dd 100%);
                color: white;
                padding: 18px 20px;
                border-radius: 16px 16px 0 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .live-chat-header h3 {
                margin: 0;
                font-size: 18px;
                font-weight: 600;
                text-shadow: 0 1px 2px rgba(0,0,0,0.1);
            }
            .live-chat-actions button {
                background: rgba(255,255,255,0.15);
                border: none;
                color: white;
                font-size: 18px;
                cursor: pointer;
                padding: 6px 10px;
                border-radius: 6px;
                transition: background 0.2s ease;
                margin-left: 6px;
            }
            .live-chat-actions button:hover {
                background: rgba(255,255,255,0.25);
            }
            .live-chat-body {
                flex: 1;
                overflow-y: auto;
                padding: 16px;
            }
            .live-chat-details-form {
                position: absolute;
                top: 56px;
                left: 0;
                right: 0;
                bottom: 0;
                background: white;
                display: flex;
                flex-direction: column;
                gap: 12px;
                padding: 24px;
                justify-content: center;
                z-index: 10;
            }
            .live-chat-details-form h4 {
                margin: 0 0 16px;
                color: #333;
                font-size: 18px;
                text-align: center;
            }
            .live-chat-details-form form {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .live-chat-details-form input {
                padding: 14px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 14px;
                color: #333;
                background: #fff;
            }
            .live-chat-details-form input:focus {
                outline: none;
                border-color: ${config.widgetColor};
                box-shadow: 0 0 0 3px ${config.widgetColor}20;
            }
            .live-chat-details-form button {
                padding: 14px;
                background: ${config.widgetColor};
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 16px;
                font-weight: 600;
                margin-top: 8px;
                transition: all 0.2s ease;
                box-shadow: 0 2px 8px ${config.widgetColor}40;
            }
            .live-chat-details-form button:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px ${config.widgetColor}50;
                filter: brightness(1.05);
            }
            .welcome-message {
                background: #f1f1f1;
                padding: 12px 16px;
                border-radius: 18px;
                margin-bottom: 12px;
                color: #333;
                max-width: 85%;
            }
            .welcome-message p {
                margin: 0;
            }
            .live-chat-messages {
                height: 100%;
                display: flex;
                flex-direction: column;
            }
            .messages-container {
                flex: 1;
                overflow-y: auto;
                padding: 16px 0;
            }
            .message {
                margin-bottom: 12px;
                display: flex;
                flex-direction: column;
            }
            .message.visitor {
                align-items: flex-end;
            }
            .message.agent {
                align-items: flex-start;
            }
            .message-bubble {
                max-width: 70%;
                padding: 10px 14px;
                border-radius: 18px;
                word-wrap: break-word;
            }
            .message.visitor .message-bubble {
                background: ${config.widgetColor};
                color: white;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .message.agent .message-bubble {
                background: #f1f1f1;
                color: #333;
            }
            .message-status {
                display: flex;
                justify-content: flex-end;
                margin-top: 4px;
                line-height: 1;
                opacity: 0.9;
            }
            .message-status.read {
                opacity: 1;
            }
            .message-status svg {
                width: 18px;
                height: 18px;
            }
            .message.visitor .message-status svg {
                stroke: white;
            }
            .live-chat-footer {
                padding: 12px 16px;
                border-top: 1px solid #eee;
                display: flex;
                gap: 8px;
                align-items: center;
                background: #fafafa;
            }
            .live-chat-footer input {
                flex: 1;
                padding: 12px 16px;
                border: 1px solid #e0e0e0;
                border-radius: 24px;
                font-size: 14px;
                color: #333;
                background: #fff;
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
            }
            .live-chat-footer input:focus {
                outline: none;
                border-color: ${config.widgetColor};
                box-shadow: 0 0 0 3px ${config.widgetColor}15;
            }
            .live-chat-footer button {
                padding: 10px 18px;
                background: ${config.widgetColor};
                color: white;
                border: none;
                border-radius: 24px;
                cursor: pointer;
                font-weight: 500;
                transition: all 0.2s ease;
            }
            .live-chat-footer button:hover {
                transform: scale(1.05);
                box-shadow: 0 2px 8px ${config.widgetColor}40;
            }
            .live-chat-footer #file-upload-btn {
                padding: 8px 12px;
                background: transparent;
                color: #666;
                font-size: 18px;
            }
            .live-chat-footer #file-upload-btn:hover {
                color: ${config.widgetColor};
                transform: scale(1.1);
                box-shadow: none;
            }
            .typing-indicator {
                display: flex;
                gap: 4px;
                padding: 8px;
            }
            .typing-indicator span {
                width: 8px;
                height: 8px;
                background: #999;
                border-radius: 50%;
                animation: typing 1.4s infinite;
            }
            .typing-indicator span:nth-child(2) {
                animation-delay: 0.2s;
            }
            .typing-indicator span:nth-child(3) {
                animation-delay: 0.4s;
            }
            @keyframes typing {
                0%, 60%, 100% { transform: translateY(0); }
                30% { transform: translateY(-10px); }
            }
            
            /* Emoji Picker Styles */
            .emoji-btn {
                background: transparent !important;
                border: none;
                font-size: 20px;
                cursor: pointer;
                padding: 6px 8px;
                border-radius: 6px;
                transition: all 0.2s ease;
            }
            .emoji-btn:hover {
                background: rgba(0,0,0,0.05) !important;
                transform: scale(1.1);
                box-shadow: none !important;
            }
            .emoji-picker {
                position: absolute;
                bottom: 45px;
                left: 0;
                background: white;
                border: 1px solid #e0e0e0;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                padding: 10px;
                z-index: 100;
                animation: fadeInUp 0.2s ease;
            }
            @keyframes fadeInUp {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .emoji-grid {
                display: grid;
                grid-template-columns: repeat(8, 1fr);
                gap: 4px;
                max-width: 280px;
            }
            .emoji-item {
                padding: 6px;
                font-size: 20px;
                cursor: pointer;
                border-radius: 6px;
                text-align: center;
                transition: all 0.15s ease;
            }
            .emoji-item:hover {
                background: #f0f0f0;
                transform: scale(1.2);
            }
        `;
        document.head.appendChild(style);
    }

    // Track page visit
    function trackPage() {
        fetch(`${config.apiUrl}/visitor/track`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                widget_key: config.widgetKey,
                visitor_key: config.visitorKey,
                page_url: window.location.href,
                page_title: document.title,
                referrer_url: document.referrer,
            }),
        })
        .then(res => res.json())
        .then(data => {
            config.sessionKey = data.session_key;
            if (data.visitor_key) {
                config.visitorKey = data.visitor_key;
                setCookie('live_chat_visitor_key', config.visitorKey, 365);
            }
            // Start heartbeat after session is established
            startHeartbeat();
        })
        .catch(err => console.error('Tracking error:', err));
    }

    // Track page changes
    function trackPageChanges() {
        let lastUrl = window.location.href;
        setInterval(() => {
            if (window.location.href !== lastUrl) {
                lastUrl = window.location.href;
                trackPage();
            }
        }, 1000);
    }

    // Check for existing chat (agent-initiated) and load messages
    function checkExistingChat() {
        if (!config.widgetKey || !config.visitorKey) return;
        if (state.chatId) return; // Already have a chat
        
        fetch(`${config.apiUrl}/chat/check-existing?widget_key=${config.widgetKey}&visitor_key=${config.visitorKey}`)
            .then(res => res.json())
            .then(data => {
                if (data.exists && data.chat_id) {
                    console.log('Found existing agent-initiated chat:', data.chat_id);
                    state.chatId = data.chat_id;
                    state.messages = data.messages || [];
                    
                    // Save to localStorage
                    localStorage.setItem('live_chat_chat_id', data.chat_id);
                    localStorage.setItem('live_chat_session_timestamp', Date.now().toString());
                    
                    // Subscribe to chat channel for real-time updates
                    initChatWebSocket();
                    
                    // Show unread badge and toast if there are unread agent messages
                    const unreadAgentMessages = state.messages.filter(m => m.sender_type === 'agent' && !m.is_read);
                    if (unreadAgentMessages.length > 0) {
                        showUnreadBadge(unreadAgentMessages.length);
                        
                        // Show toast with the latest agent message
                        const latestMessage = unreadAgentMessages[unreadAgentMessages.length - 1];
                        showToastNotification(
                            latestMessage.message || 'New message',
                            latestMessage.sender_name || 'Support Agent',
                            latestMessage.sender_avatar || null
                        );
                    }
                }
            })
            .catch(err => console.log('Check existing chat error:', err));
    }

    // Initialize WebSocket
    function initWebSocket() {
        // WebSocket connection will be set up when chat is created
        // For now, we'll use polling as fallback
    }

    // Start heartbeat to keep session alive
    function startHeartbeat() {
        if (!config.sessionKey) return;
        
        // Send heartbeat every 30 seconds
        state.heartbeatInterval = setInterval(() => {
            fetch(`${config.apiUrl}/visitor/heartbeat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_key: config.sessionKey }),
            }).catch(err => console.log('Heartbeat failed:', err));
        }, 30000);

        // Handle page visibility change
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                // Page visible again - send heartbeat immediately to ensure we're marked online
                fetch(`${config.apiUrl}/visitor/heartbeat`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_key: config.sessionKey }),
                }).catch(err => console.log('Heartbeat failed:', err));
            }
            // Don't send offline when tab is hidden - user is still on the page
            // Let the server-side timeout handle marking offline after 2 minutes of inactivity
        });

        // Handle page unload
        window.addEventListener('beforeunload', () => {
            sendOfflineSignal();
        });
    }

    // Send offline signal using beacon API (reliable on page unload)
    function sendOfflineSignal() {
        if (!config.sessionKey) return;
        
        const data = JSON.stringify({ session_key: config.sessionKey });
        
        // Use sendBeacon for reliable delivery on page unload
        if (navigator.sendBeacon) {
            const blob = new Blob([data], { type: 'application/json' });
            navigator.sendBeacon(`${config.apiUrl}/visitor/offline`, blob);
        } else {
            // Fallback to sync XHR
            const xhr = new XMLHttpRequest();
            xhr.open('POST', `${config.apiUrl}/visitor/offline`, false);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.send(data);
        }
    }

    // Attach event listeners
    function attachEventListeners() {
        document.getElementById('details-form')?.addEventListener('submit', handleDetailsFormSubmit);
        
        const messageInput = document.getElementById('message-input');
        messageInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                window.LiveChatWidget.sendMessage();
            }
        });
        
        // Send typing indicator with debounce
        let typingTimeout = null;
        messageInput?.addEventListener('input', () => {
            if (!state.chatId || !config.visitorKey) return;
            
            // Clear previous timeout
            if (typingTimeout) clearTimeout(typingTimeout);
            
            // Send typing indicator
            fetch(`${config.apiUrl}/chat/${state.chatId}/typing`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ visitor_key: config.visitorKey }),
            }).catch(() => {}); // Ignore errors
            
            // Don't send again for 2 seconds
            typingTimeout = setTimeout(() => {
                typingTimeout = null;
            }, 2000);
        });
        
        document.getElementById('file-upload-btn')?.addEventListener('click', () => {
            document.getElementById('file-input')?.click();
        });
        document.getElementById('file-input')?.addEventListener('change', handleFileUpload);
        
        // Emoji picker event listeners
        const emojiPickerBtn = document.getElementById('emoji-picker-btn');
        const emojiPicker = document.getElementById('emoji-picker');
        
        emojiPickerBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            const isVisible = emojiPicker.style.display !== 'none';
            emojiPicker.style.display = isVisible ? 'none' : 'block';
        });
        
        // Insert emoji on click
        document.querySelectorAll('.emoji-item').forEach(item => {
            item.addEventListener('click', () => {
                const emoji = item.dataset.emoji;
                const input = document.getElementById('message-input');
                if (input) {
                    const start = input.selectionStart;
                    const end = input.selectionEnd;
                    const text = input.value;
                    input.value = text.substring(0, start) + emoji + text.substring(end);
                    input.focus();
                    input.selectionStart = input.selectionEnd = start + emoji.length;
                }
                emojiPicker.style.display = 'none';
            });
        });
        
        // Close picker when clicking outside
        document.addEventListener('click', (e) => {
            if (emojiPicker && !emojiPicker.contains(e.target) && e.target !== emojiPickerBtn) {
                emojiPicker.style.display = 'none';
            }
        });
    }

    // Handle details form submission (shown on first message)
    function handleDetailsFormSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const pendingMessage = formData.get('pending_message');
        const name = formData.get('name');
        const email = formData.get('email');
        const phone = formData.get('phone');
        
        // Mark that user has submitted details and save to localStorage
        state.hasSubmittedDetails = true;
        state.visitorDetails = { name, email, phone };
        localStorage.setItem('live_chat_details_submitted', 'true');
        localStorage.setItem('live_chat_visitor_details', JSON.stringify({ name, email, phone }));

        fetch(`${config.apiUrl}/chat/create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                widget_key: config.widgetKey,
                visitor_key: config.visitorKey,
                session_key: config.sessionKey,
                name: name,
                email: email,
                phone: phone,
                message: pendingMessage, // Include the pending message
            }),
        })
        .then(res => res.json())
        .then(data => {
            state.chatId = data.chat_id;
            // Save chat ID and timestamp to localStorage so we can resume
            localStorage.setItem('live_chat_chat_id', data.chat_id);
            localStorage.setItem('live_chat_session_timestamp', Date.now().toString());
            // Hide form, show messages
            document.getElementById('live-chat-details-form').style.display = 'none';
            document.getElementById('live-chat-messages').style.display = 'block';
            document.getElementById('live-chat-footer').style.display = 'flex';
            loadMessages();
            initChatWebSocket();
        })
        .catch(err => console.error('Chat creation error:', err));
    }

    // Load saved visitor details from localStorage
    function loadSavedVisitorDetails() {
        const submitted = localStorage.getItem('live_chat_details_submitted') === 'true';
        const savedDetails = localStorage.getItem('live_chat_visitor_details');
        const savedChatId = localStorage.getItem('live_chat_chat_id');
        const savedTimestamp = localStorage.getItem('live_chat_session_timestamp');
        
        // Check if session has expired (24 hours)
        const SESSION_EXPIRY_MS = 24 * 60 * 60 * 1000; // 24 hours
        if (savedTimestamp && (Date.now() - parseInt(savedTimestamp)) > SESSION_EXPIRY_MS) {
            console.log('Session expired (24 hours), clearing localStorage');
            clearSavedSession();
            return;
        }
        
        if (submitted && savedDetails) {
            state.hasSubmittedDetails = true;
            state.visitorDetails = JSON.parse(savedDetails);
            
            if (savedChatId) {
                state.chatId = savedChatId;
                // Returning visitor with existing chat - load messages
                loadMessages();
                initChatWebSocket();
            }
        }
    }

    // Load messages
    function loadMessages() {
        if (!state.chatId) return;

        fetch(`${config.apiUrl}/chat/${state.chatId}/messages?visitor_key=${config.visitorKey}`)
            .then(res => {
                if (res.status === 403 || res.status === 404) {
                    // Session expired or chat deleted - clear localStorage and start fresh
                    console.log('Session expired or chat not found, clearing localStorage');
                    clearSavedSession();
                    state.chatId = null;
                    state.hasSubmittedDetails = false;
                    renderMessages(); // Show welcome message
                    throw new Error('Session expired');
                }
                return res.json();
            })
            .then(data => {
                // Messages now come from API in ascending order (oldest first)
                state.messages = data.messages || [];
                renderMessages();
            })
            .catch(err => {
                if (err.message !== 'Session expired') {
                    console.error('Load messages error:', err);
                }
            });
    }
    
    // Clear saved session from localStorage
    function clearSavedSession() {
        localStorage.removeItem('live_chat_details_submitted');
        localStorage.removeItem('live_chat_visitor_details');
        localStorage.removeItem('live_chat_chat_id');
        localStorage.removeItem('live_chat_session_timestamp');
    }

    // Render messages
    function renderMessages() {
        const container = document.getElementById('messages-container');
        
        if (state.messages.length === 0) {
            container.innerHTML = `
                <div class="welcome-message">
                    <p>Welcome! How can we help you today?</p>
                </div>
            `;
        } else {
            container.innerHTML = state.messages.map(msg => {
                // Determine message state styles
                const isSending = msg._sending;
                const isFailed = msg._failed;
                const bubbleStyle = isSending ? 'opacity: 0.7;' : (isFailed ? 'background: #ff4757 !important;' : '');
                
                // Determine status icon
                let statusIcon = '';
                if (msg.sender_type === 'visitor') {
                    if (isSending) {
                        // Clock icon for sending
                        statusIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>';
                    } else if (isFailed) {
                        // Error icon for failed
                        statusIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ff4757" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
                    } else if (msg.is_read) {
                        // Double check for read
                        statusIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path><path d="M20 6L9 17l-5-5" style="transform: translate(5px, 0)"/></svg>';
                    } else {
                        // Single check for sent
                        statusIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>';
                    }
                }
                
                return `
                <div class="message ${msg.sender_type}">
                    ${msg.sender_type === 'agent' && msg.sender_name ? `
                        <div class="agent-name" style="font-size: 11px; color: #666; margin-bottom: 2px;">
                            ${escapeHtml(msg.sender_name)}
                        </div>
                    ` : ''}
                    <div class="message-bubble" style="${bubbleStyle}">
                        ${msg.message_type === 'file' ? 
                            (msg.file_type && msg.file_type.startsWith('image/') ? 
                                `<img src="${config.apiUrl}/chat/${state.chatId}/file/${msg.id}/download?visitor_key=${config.visitorKey}" 
                                      alt="${escapeHtml(msg.file_name)}" 
                                      class="max-w-full rounded cursor-pointer" 
                                      onclick="window.open(this.src, '_blank')"
                                      style="max-height: 200px;">
                                 <div class="text-xs mt-1" style="color: ${msg.sender_type === 'visitor' ? '#fff' : '#333'}">${escapeHtml(msg.file_name)}</div>` :
                                `<a href="${config.apiUrl}/chat/${state.chatId}/file/${msg.id}/download?visitor_key=${config.visitorKey}" 
                                    download="${escapeHtml(msg.file_name)}"
                                    class="file-download-link"
                                    style="color: ${msg.sender_type === 'visitor' ? '#fff' : '#333'}; text-decoration: underline;">
                                    📎 ${escapeHtml(msg.file_name)}
                                 </a>`
                            ) :
                            escapeHtml(msg.message || '')
                        }
                    </div>
                    ${msg.sender_type === 'visitor' ? `
                        <div class="message-status ${msg.is_read ? 'read' : ''}" id="msg-status-${msg.id}">
                            ${statusIcon}
                        </div>
                    ` : ''}
                </div>
            `}).join('');
        }
        container.scrollTop = container.scrollHeight;
    }

    // Send message
    function sendMessage() {
        const input = document.getElementById('message-input');
        const message = input.value.trim();
        if (!message) return;
        
        // Check if user has submitted details
        const hasDetails = state.hasSubmittedDetails || localStorage.getItem('live_chat_details_submitted') === 'true';
        
        // If no details yet, show the form with pending message
        if (!hasDetails || !state.chatId) {
            document.getElementById('pending-message').value = message;
            document.getElementById('live-chat-messages').style.display = 'none';
            document.getElementById('live-chat-details-form').style.display = 'block';
            document.getElementById('live-chat-footer').style.display = 'none';
            input.value = '';
            return;
        }

        // Show message immediately (optimistic UI) with "sending" state
        const tempMessage = {
            id: 'temp-' + Date.now(),
            message: message,
            sender_type: 'visitor',
            created_at: new Date().toISOString(),
            _sending: true // Mark as sending for UI state
        };
        state.messages.push(tempMessage);
        renderMessages();
        input.value = '';

        // User has submitted details, send message directly
        fetch(`${config.apiUrl}/chat/${state.chatId}/message`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                visitor_key: config.visitorKey,
                message: message,
            }),
        })
        .then(res => res.json())
        .then(data => {
            // Update temp message with real data and mark as sent
            const tempIndex = state.messages.findIndex(m => m.id === tempMessage.id);
            if (tempIndex !== -1 && data.message) {
                state.messages[tempIndex] = data.message;
                renderMessages(); // Re-render to update the status
            }
        })
        .catch(err => {
            console.error('Send message error:', err);
            // Mark message as failed instead of removing
            const tempIndex = state.messages.findIndex(m => m.id === tempMessage.id);
            if (tempIndex !== -1) {
                state.messages[tempIndex]._failed = true;
                state.messages[tempIndex]._sending = false;
                renderMessages();
            }
            // Show error notification
            showSendErrorToast(message);
        });
    }
    
    // Show send error toast with retry option
    function showSendErrorToast(message) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 100px;
            right: 20px;
            background: #ff4757;
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(255, 71, 87, 0.3);
            max-width: 280px;
            z-index: 999999;
            font-size: 14px;
            cursor: pointer;
        `;
        toast.innerHTML = `
            <div style="font-weight: 600; margin-bottom: 4px;">Message failed to send</div>
            <div style="opacity: 0.9;">Tap to retry</div>
        `;
        toast.onclick = () => {
            document.getElementById('message-input').value = message;
            toast.remove();
        };
        document.body.appendChild(toast);
        
        setTimeout(() => toast.remove(), 5000);
    }

    // Handle file upload
    function handleFileUpload(e) {
        const file = e.target.files[0];
        if (!file || !state.chatId) return;

        const formData = new FormData();
        formData.append('visitor_key', config.visitorKey);
        formData.append('file', file);

        fetch(`${config.apiUrl}/chat/${state.chatId}/file`, {
            method: 'POST',
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            state.messages.push(data.message);
            renderMessages();
        })
        .catch(err => console.error('File upload error:', err));
    }

    // Initialize chat WebSocket with reconnection logic
    function initChatWebSocket() {
        // Track reconnection state
        if (!state.wsReconnectAttempts) state.wsReconnectAttempts = 0;
        const maxReconnectAttempts = 5;
        const baseReconnectDelay = 1000; // 1 second
        
        // Try to use Pusher if available
        if (window.Pusher && config.wsKey) {
            try {
                // Cleanup existing connection if any
                if (state.pusher) {
                    try {
                        state.pusher.disconnect();
                    } catch (e) {
                        console.log('Error disconnecting previous Pusher:', e);
                    }
                }
                
                const pusher = new Pusher(config.wsKey, {
                    wsHost: config.wsHost || window.location.hostname,
                    wsPort: config.wsPort || 8080,
                    wssPort: config.wsPort || 8080,
                    forceTLS: config.wsScheme === 'https',
                    enabledTransports: ['ws', 'wss'],
                    disableStats: true,
                    cluster: 'mt1'
                });
                
                // Handle connection state changes
                pusher.connection.bind('connected', function() {
                    console.log('WebSocket connected for chat', state.chatId);
                    state.wsReconnectAttempts = 0; // Reset on success
                    state.wsConnected = true;
                    
                    // Refresh messages on reconnect to catch any missed
                    if (state.wasDisconnected) {
                        loadMessages();
                        state.wasDisconnected = false;
                    }
                });
                
                pusher.connection.bind('disconnected', function() {
                    console.log('WebSocket disconnected, will attempt reconnect...');
                    state.wsConnected = false;
                    state.wasDisconnected = true;
                });
                
                pusher.connection.bind('error', function(error) {
                    console.log('WebSocket error:', error);
                    state.wsConnected = false;
                });
                
                pusher.connection.bind('unavailable', function() {
                    console.log('WebSocket unavailable, scheduling reconnect...');
                    state.wsReconnectAttempts++;
                    
                    if (state.wsReconnectAttempts < maxReconnectAttempts) {
                        // Exponential backoff: 1s, 2s, 4s, 8s, 16s (max 30s)
                        const delay = Math.min(baseReconnectDelay * Math.pow(2, state.wsReconnectAttempts - 1), 30000);
                        console.log(`Reconnect attempt ${state.wsReconnectAttempts} in ${delay}ms`);
                        
                        setTimeout(() => {
                            if (!state.wsConnected && state.chatId) {
                                initChatWebSocket();
                            }
                        }, delay);
                    } else {
                        console.log('Max reconnect attempts reached, falling back to polling');
                        pollForMessages();
                    }
                });

                // Use public channel (no auth required)
                const channel = pusher.subscribe(`chat.${state.chatId}`);

                channel.bind('message.sent', function(data) {
                    // Only add if not our own message (avoid duplicates)
                    if (data.sender_type === 'agent' && !state.messages.find(m => m.id === data.id)) {
                        state.messages.push(data);
                        renderMessages();
                        // Play notification sound for agent messages
                        playNotificationSound();
                        // Show visual indicator if chat window is closed
                        if (!state.isOpen) {
                            showUnreadBadge();
                            // Pass agent name and avatar to toast
                            showToastNotification(
                                data.message || 'New message',
                                data.sender_name || 'Support Agent',
                                data.sender_avatar || null
                            );
                        }
                    }
                });

                channel.bind('agent.typing', function(data) {
                    showTypingIndicator();
                });

                channel.bind('messages.read', function(data) {
                    if (data.reader_type === 'agent') {
                        data.message_ids.forEach(id => {
                            const el = document.getElementById(`msg-status-${id}`);
                            if (el) {
                                el.classList.add('read');
                                el.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path><path d="M20 6L9 17l-5-5" style="transform: translate(5px, 0)"/></svg>';
                            }
                        });
                    }
                });

                state.pusher = pusher;
                state.channel = channel;
            } catch (e) {
                console.error('WebSocket connection failed:', e);
                // Fallback to polling
                pollForMessages();
            }
        } else {
            console.log('Pusher not available, using polling');
            // Fallback to polling
            pollForMessages();
        }
    }

    // Poll for new messages (fallback)
    function pollForMessages() {
        if (!state.chatId) return;

        setInterval(() => {
            fetch(`${config.apiUrl}/chat/${state.chatId}/messages?visitor_key=${config.visitorKey}&page=1`)
                .then(res => res.json())
                .then(data => {
                    const newMessages = data.messages.filter(msg =>
                        !state.messages.find(m => m.id === msg.id)
                    );
                    if (newMessages.length > 0) {
                        state.messages.push(...newMessages);
                        renderMessages();
                    }
                })
                .catch(err => console.error('Polling error:', err));
        }, 3000);
    }

    function showTypingIndicator() {
        const indicator = document.getElementById('typing-indicator');
        if (indicator) {
            indicator.style.display = 'flex';
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 3000);
        }
    }

    // Play notification sound using Web Audio API - Pleasant chat notification tone
    function playNotificationSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Create a pleasant two-tone notification sound
            const playTone = (freq, startTime, duration) => {
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = freq;
                oscillator.type = 'sine';
                
                // Envelope: quick attack, sustain, quick release
                gainNode.gain.setValueAtTime(0, audioContext.currentTime + startTime);
                gainNode.gain.linearRampToValueAtTime(0.15, audioContext.currentTime + startTime + 0.02);
                gainNode.gain.setValueAtTime(0.15, audioContext.currentTime + startTime + duration - 0.02);
                gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + startTime + duration);
                
                oscillator.start(audioContext.currentTime + startTime);
                oscillator.stop(audioContext.currentTime + startTime + duration);
            };
            
            // Play a pleasant "ding-ding" sound (like iMessage)
            playTone(880, 0, 0.12);      // A5
            playTone(1318.5, 0.12, 0.15); // E6 (higher note)
            
        } catch (e) {
            console.log('Could not play notification sound:', e);
        }
    }

    // Show unread badge on chat button and update tab title
    function showUnreadBadge(count = 1) {
        // Increment unread count
        state.unreadCount += count;
        
        const button = document.querySelector('.live-chat-button');
        if (button) {
            // Create or update badge
            let badge = button.querySelector('.unread-badge');
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'unread-badge';
                button.style.position = 'relative';
                button.appendChild(badge);
            }
            
            // Style badge with count
            if (state.unreadCount > 9) {
                badge.textContent = '9+';
                badge.style.cssText = 'position:absolute;top:-6px;right:-6px;min-width:20px;height:20px;background:#ff4757;border-radius:10px;border:2px solid #1a1a1a;font-size:10px;font-weight:bold;display:flex;align-items:center;justify-content:center;padding:0 4px;';
            } else if (state.unreadCount > 1) {
                badge.textContent = state.unreadCount;
                badge.style.cssText = 'position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;background:#ff4757;border-radius:9px;border:2px solid #1a1a1a;font-size:10px;font-weight:bold;display:flex;align-items:center;justify-content:center;';
            } else {
                badge.textContent = '';
                badge.style.cssText = 'position:absolute;top:-4px;right:-4px;width:12px;height:12px;background:#ff4757;border-radius:50%;border:2px solid #1a1a1a;';
            }
        }
        
        // Update browser tab title
        updateTabTitle();
    }
    
    // Update browser tab title with unread count
    function updateTabTitle() {
        if (state.unreadCount > 0) {
            document.title = `(${state.unreadCount}) ${state.originalTitle}`;
        } else {
            document.title = state.originalTitle;
        }
    }

    // Hide unread badge when chat opens
    function hideUnreadBadge() {
        state.unreadCount = 0;
        const badge = document.querySelector('.live-chat-button .unread-badge');
        if (badge) badge.remove();
        // Reset tab title
        updateTabTitle();
    }

    // Show toast notification for new messages (with agent details)
    function showToastNotification(message, agentName = null, agentAvatar = null) {
        // Remove existing toast if any
        let toast = document.getElementById('live-chat-toast');
        if (toast) {
            toast.remove();
        }
        
        // Get display name (use agent name or fallback)
        const displayName = agentName || 'Support Agent';
        const initial = displayName.charAt(0).toUpperCase();
        
        // Create new toast with agent info - bigger and more modern
        toast = document.createElement('div');
        toast.id = 'live-chat-toast';
        toast.style.cssText = `
            position: fixed;
            bottom: 100px;
            right: 20px;
            background: white;
            color: #333;
            padding: 16px 20px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2), 0 4px 12px rgba(0,0,0,0.1);
            max-width: 360px;
            min-width: 280px;
            z-index: 999998;
            cursor: pointer;
            animation: slideInUp 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid ${config.widgetColor};
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        `;
        
        // Add hover effect
        toast.onmouseenter = () => {
            toast.style.transform = 'translateY(-2px)';
            toast.style.boxShadow = '0 14px 50px rgba(0,0,0,0.25), 0 6px 16px rgba(0,0,0,0.15)';
        };
        toast.onmouseleave = () => {
            toast.style.transform = 'translateY(0)';
            toast.style.boxShadow = '0 10px 40px rgba(0,0,0,0.2), 0 4px 12px rgba(0,0,0,0.1)';
        };
        
        // Build avatar HTML - bigger avatar
        let avatarHtml;
        if (agentAvatar) {
            avatarHtml = `<img src="${agentAvatar}" alt="${escapeHtml(displayName)}" style="width: 52px; height: 52px; border-radius: 50%; object-fit: cover; border: 2px solid ${config.widgetColor};">`;
        } else {
            avatarHtml = `<div style="width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, ${config.widgetColor} 0%, ${config.widgetColor}cc 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; box-shadow: 0 2px 8px ${config.widgetColor}40;">${initial}</div>`;
        }
        
        toast.innerHTML = `
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="flex-shrink: 0;">
                    ${avatarHtml}
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 700; font-size: 15px; color: #1a1a1a; margin-bottom: 4px;">${escapeHtml(displayName)}</div>
                    <div style="font-size: 14px; color: #555; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${escapeHtml(message)}</div>
                </div>
                <button onclick="event.stopPropagation(); this.closest('#live-chat-toast').remove();" style="flex-shrink: 0; background: #f5f5f5; border: none; color: #666; cursor: pointer; padding: 6px; font-size: 16px; line-height: 1; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; transition: all 0.15s ease;" onmouseenter="this.style.background='#eee';this.style.color='#333'" onmouseleave="this.style.background='#f5f5f5';this.style.color='#666'">&times;</button>
            </div>
        `;
        
        // Click to open widget
        toast.onclick = function() {
            window.LiveChatWidget.toggle();
            toast.remove();
        };

        // Add animation keyframes if not already added
        if (!document.getElementById('toast-animation-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-animation-styles';
            style.textContent = `
                @keyframes slideInUp {
                    from { transform: translateY(20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(toast);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (toast && toast.parentNode) {
                toast.style.animation = 'slideInUp 0.3s ease-out reverse';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    }

    // Utility functions
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${date.toUTCString()};path=/`;
    }

    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Get icon SVG based on icon type
    function getIconSvg(iconType) {
        const icons = {
            'chat': `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>`,
            'support': `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>`,
            'message': `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>`,
            'help': `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`,
        };
        return icons[iconType] || icons['chat'];
    }
    
    // Subscribe to visitor channel for proactive messages
    function subscribeToVisitorChannel() {
        if (!config.visitorKey || !config.wsKey) {
            console.log('Cannot subscribe to visitor channel - no visitor key or ws key');
            return;
        }
        
        // Create Pusher instance if not already exists
        if (!state.pusher && window.Pusher) {
            state.pusher = new Pusher(config.wsKey, {
                wsHost: config.wsHost || window.location.hostname,
                wsPort: config.wsPort || 8080,
                wssPort: config.wsPort || 8080,
                forceTLS: config.wsScheme === 'https',
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
                cluster: 'mt1'
            });
        }
        
        if (!state.pusher) {
            console.log('Cannot subscribe to visitor channel - Pusher not available');
            return;
        }
        
        const channel = state.pusher.subscribe('visitor.' + config.visitorKey);
        
        channel.bind('proactive.message', function(data) {
            console.log('Received proactive message:', data);
            
            // Push to messages state so it's available when chat opens
            const msgObj = {
                id: 'proactive-' + Date.now(),
                message: data.message,
                sender_type: 'agent',
                sender_name: data.agent_name,
                created_at: data.timestamp || new Date().toISOString()
            };
            
            state.messages.push(msgObj);
            
            // If chat is OPEN, update UI immediately
            if (state.isOpen) {
               // Hide details form and show messages so visitor sees the agent's message
               // The form will reappear when they try to reply (handled in sendMessage)
               const form = document.getElementById('live-chat-details-form');
               const msgs = document.getElementById('live-chat-messages');
               const footer = document.getElementById('live-chat-footer');
               
               if (form) form.style.display = 'none';
               if (msgs) {
                   msgs.style.display = 'block';
                   renderMessages();
                   const container = document.getElementById('messages-container');
                   if (container) container.scrollTop = container.scrollHeight;
               }
               if (footer) footer.style.display = 'flex';
            }

            showProactiveBubble(data.message, data.agent_name, data.agent_avatar);
            playNotificationSound();
        });
        
        console.log('Subscribed to visitor channel:', config.visitorKey);
    }
    
    // Show proactive message bubble
    function showProactiveBubble(message, agentName, agentAvatar) {
        const bubble = document.getElementById('proactive-bubble');
        const avatarEl = document.getElementById('proactive-avatar');
        const nameEl = document.getElementById('proactive-agent-name');
        const textEl = document.getElementById('proactive-message-text');
        
        if (!bubble) return;
        
        // Set content
        if (agentAvatar) {
            avatarEl.innerHTML = `<img src="${agentAvatar}" alt="${agentName}">`;
        } else {
            avatarEl.textContent = agentName.charAt(0).toUpperCase();
        }
        nameEl.textContent = agentName;
        textEl.textContent = message;
        
        // Show bubble
        bubble.style.display = 'block';
        
        // Play notification sound
        playNotificationSound();
        showUnreadBadge();
        
        // Click bubble to open chat
        bubble.onclick = function(e) {
            if (e.target.classList.contains('proactive-close')) return;
            bubble.style.display = 'none';
            state.isOpen = true;
            document.getElementById('live-chat-window').classList.add('open');
            hideUnreadBadge();
            
            // Show messages if any exist (proactive)
            if (state.messages.length > 0) {
                 document.getElementById('live-chat-details-form').style.display = 'none';
                 document.getElementById('live-chat-messages').style.display = 'block';
                 document.getElementById('live-chat-footer').style.display = 'flex';
                 renderMessages();
                 setTimeout(() => {
                    const container = document.getElementById('messages-container');
                    if (container) container.scrollTop = container.scrollHeight;
                 }, 50);
            }
        };
        
        // Auto-hide after 30 seconds
        setTimeout(() => {
            if (bubble.style.display !== 'none') {
                bubble.style.display = 'none';
            }
        }, 30000);
    }
    
    // Close proactive bubble
    function closeProactiveBubble() {
        const bubble = document.getElementById('proactive-bubble');
        if (bubble) {
            bubble.style.display = 'none';
        }
    }

    // Public API
    window.LiveChatWidget = {
        init: init,
        toggle: function() {
            state.isOpen = !state.isOpen;
            const window = document.getElementById('live-chat-window');
            const bubble = document.getElementById('proactive-bubble');
            if (state.isOpen) {
                window.classList.add('open');
                hideUnreadBadge(); // Clear unread indicator when opened
                if (bubble) bubble.style.display = 'none'; // Hide proactive bubble when chat opens
                
                // If we have existing messages (agent-initiated chat or proactive), show them
                if (state.messages.length > 0) {
                    document.getElementById('live-chat-details-form').style.display = 'none';
                    document.getElementById('live-chat-messages').style.display = 'block';
                    document.getElementById('live-chat-footer').style.display = 'flex';
                    renderMessages();
                    setTimeout(() => {
                        const container = document.getElementById('messages-container');
                        if (container) container.scrollTop = container.scrollHeight;
                    }, 50);
                }
                
                markMessagesAsRead();
                
                // Remove toast if open
                const toast = document.getElementById('live-chat-toast');
                if (toast) toast.remove();
            } else {
                window.classList.remove('open');
            }
        },
        minimize: function() {
            state.isMinimized = true;
            document.getElementById('live-chat-window').style.display = 'none';
        },
        close: function() {
            state.isOpen = false;
            document.getElementById('live-chat-window').classList.remove('open');
        },
        sendMessage: sendMessage,
        closeProactiveBubble: closeProactiveBubble,
        markAsRead: markMessagesAsRead
    };

    function markMessagesAsRead() {
        if (!state.chatId || !state.isOpen) return;

        fetch(`${config.apiUrl}/chat/${state.chatId}/read`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                visitor_key: config.visitorKey
            }),
        }).catch(err => console.error('Mark read error:', err));
    }
})();


