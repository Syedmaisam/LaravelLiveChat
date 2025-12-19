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
    };

    // Load Pusher library dynamically
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
                    // Also init WebSocket for general listening
                    initWebSocket();
                });
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
        const widget = document.createElement('div');
        widget.id = 'live-chat-widget';
        widget.innerHTML = `
            <div class="live-chat-button" onclick="window.LiveChatWidget.toggle()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>
            <div class="live-chat-window" id="live-chat-window">
                <div class="live-chat-header">
                    <h3>Chat with us</h3>
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
                                <p>Welcome! How can we help you today?</p>
                            </div>
                        </div>
                        <div class="typing-indicator" id="typing-indicator" style="display: none;">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
                <div class="live-chat-footer" id="live-chat-footer">
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
            .live-chat-button {
                width: 60px;
                height: 60px;
                background: #007bff;
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
            .live-chat-window {
                position: absolute;
                bottom: 80px;
                right: 0;
                width: 380px;
                height: 600px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.2);
                display: none;
                flex-direction: column;
            }
            .live-chat-window.open {
                display: flex;
            }
            .live-chat-header {
                background: #007bff;
                color: white;
                padding: 16px;
                border-radius: 12px 12px 0 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .live-chat-header h3 {
                margin: 0;
                font-size: 18px;
            }
            .live-chat-actions button {
                background: transparent;
                border: none;
                color: white;
                font-size: 20px;
                cursor: pointer;
                padding: 0 8px;
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
                border-color: #007bff;
            }
            .live-chat-details-form button {
                padding: 14px;
                background: #007bff;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 16px;
                font-weight: 500;
                margin-top: 8px;
            }
            .live-chat-details-form button:hover {
                background: #0056b3;
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
                background: #007bff;
                color: white;
            }
            .message.agent .message-bubble {
                background: #f1f1f1;
                color: #333;
            }
            .live-chat-footer {
                padding: 12px;
                border-top: 1px solid #eee;
                display: flex;
                gap: 8px;
                align-items: center;
            }
            .live-chat-footer input {
                flex: 1;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 20px;
                font-size: 14px;
                color: #333;
                background: #fff;
            }
            .live-chat-footer button {
                padding: 10px 16px;
                background: #007bff;
                color: white;
                border: none;
                border-radius: 20px;
                cursor: pointer;
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

    // Initialize WebSocket
    function initWebSocket() {
        // WebSocket connection will be set up when chat is created
        // For now, we'll use polling as fallback
    }

    // Attach event listeners
    function attachEventListeners() {
        document.getElementById('details-form')?.addEventListener('submit', handleDetailsFormSubmit);
        document.getElementById('message-input')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                window.LiveChatWidget.sendMessage();
            }
        });
        document.getElementById('file-upload-btn')?.addEventListener('click', () => {
            document.getElementById('file-input')?.click();
        });
        document.getElementById('file-input')?.addEventListener('change', handleFileUpload);
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
            // Save chat ID to localStorage so we can resume
            localStorage.setItem('live_chat_chat_id', data.chat_id);
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
            .then(res => res.json())
            .then(data => {
                // Messages now come from API in ascending order (oldest first)
                state.messages = data.messages || [];
                renderMessages();
            })
            .catch(err => console.error('Load messages error:', err));
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
            container.innerHTML = state.messages.map(msg => `
                <div class="message ${msg.sender_type}">
                    <div class="message-bubble">${escapeHtml(msg.message || msg.file_name || '')}</div>
                </div>
            `).join('');
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

        // Show message immediately (optimistic UI)
        const tempMessage = {
            id: 'temp-' + Date.now(),
            message: message,
            sender_type: 'visitor',
            created_at: new Date().toISOString()
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
            // Update temp message with real ID
            const tempIndex = state.messages.findIndex(m => m.id === tempMessage.id);
            if (tempIndex !== -1 && data.message) {
                state.messages[tempIndex] = data.message;
            }
        })
        .catch(err => {
            console.error('Send message error:', err);
            // Remove temp message on error
            state.messages = state.messages.filter(m => m.id !== tempMessage.id);
            renderMessages();
        });
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

    // Initialize chat WebSocket
    function initChatWebSocket() {
        // Try to use Pusher if available
        if (window.Pusher && config.wsKey) {
            try {
                const pusher = new Pusher(config.wsKey, {
                    wsHost: config.wsHost || window.location.hostname,
                    wsPort: config.wsPort || 8080,
                    wssPort: config.wsPort || 8080,
                    forceTLS: config.wsScheme === 'https',
                    enabledTransports: ['ws', 'wss'],
                    disableStats: true,
                    cluster: 'mt1'
                });

                // Use public channel (no auth required)
                const channel = pusher.subscribe(`chat.${state.chatId}`);

                channel.bind('message.sent', function(data) {
                    // Only add if not our own message (avoid duplicates)
                    if (data.sender_type === 'agent' && !state.messages.find(m => m.id === data.id)) {
                        state.messages.push(data);
                        renderMessages();
                    }
                });

                channel.bind('agent.typing', function(data) {
                    showTypingIndicator();
                });

                state.pusher = pusher;
                state.channel = channel;
                console.log('WebSocket connected for chat', state.chatId);
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

    // Public API
    window.LiveChatWidget = {
        init: init,
        toggle: function() {
            state.isOpen = !state.isOpen;
            const window = document.getElementById('live-chat-window');
            if (state.isOpen) {
                window.classList.add('open');
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
    };
})();


