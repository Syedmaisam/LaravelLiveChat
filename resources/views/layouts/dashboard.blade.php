<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Vision Tech Chat') }} - @yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --gold: #D4AF37;
            --gold-light: #F4D03F;
            --gold-dark: #B8860B;
            --dark-bg: #0D0D0D;
            --dark-card: #1A1A1A;
            --dark-border: #2A2A2A;
            --dark-hover: #252525;
        }
        * { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        
        /* Scrollbars */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--dark-bg); }
        ::-webkit-scrollbar-thumb { background: var(--dark-border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #444; }
        
        /* Gold gradient text */
        .text-gold-gradient {
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 50%, var(--gold-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Gold glow */
        .gold-glow { box-shadow: 0 0 20px rgba(212, 175, 55, 0.3); }
        
        /* Animations */
        @keyframes pulse-gold {
            0%, 100% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(212, 175, 55, 0); }
        }
        .pulse-gold { animation: pulse-gold 2s infinite; }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-[#0D0D0D] text-gray-100 overflow-hidden antialiased">
    <div class="flex flex-col h-full">
        
        <!-- TOP HEADER BAR -->
        <header class="h-14 bg-[#1A1A1A] border-b border-[#2A2A2A] flex items-center justify-between px-6 flex-shrink-0 z-50">
            <!-- Logo & Brand -->
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#F4D03F] via-[#D4AF37] to-[#B8860B] flex items-center justify-center shadow-lg">
                        <span class="text-black font-bold text-sm">VT</span>
                    </div>
                    <span class="font-bold text-lg text-gold-gradient">VisionTech</span>
                </div>
                <div class="h-6 w-px bg-[#2A2A2A]"></div>
                <span class="text-sm text-gray-400">Live Chat</span>
            </div>

            <!-- Center Nav -->
            <nav class="hidden md:flex items-center space-x-1">
                @if(Auth::user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-all text-gray-400 hover:text-white hover:bg-white/5">
                    Dashboard
                </a>
                @endif

                <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('dashboard') && !request()->routeIs('dashboard.monitoring') && !request()->routeIs('dashboard.reporting') ? 'bg-[#D4AF37]/10 text-[#D4AF37]' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                    Live Chat
                </a>
                <a href="{{ route('dashboard.monitoring') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('dashboard.monitoring') ? 'bg-[#D4AF37]/10 text-[#D4AF37]' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                    Visitors
                </a>
                <a href="{{ route('dashboard.reporting') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('dashboard.reporting') ? 'bg-[#D4AF37]/10 text-[#D4AF37]' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                    Analytics
                </a>
                <a href="{{ route('clients.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('clients.*') ? 'bg-[#D4AF37]/10 text-[#D4AF37]' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                    Clients
                </a>
                @if(Auth::user()->isAdmin())
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.users.*') ? 'bg-[#D4AF37]/10 text-[#D4AF37]' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                    Team
                </a>
                @endif
            </nav>

            <!-- Right: User -->
            <div class="flex items-center space-x-4">
                <!-- Status Indicator -->
                <div class="flex items-center space-x-2 px-3 py-1.5 bg-emerald-500/10 rounded-full">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    <span class="text-xs text-emerald-400 font-medium">Online</span>
                </div>

                <!-- User Menu -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-white/5 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#D4AF37] to-[#B8860B] flex items-center justify-center text-black text-xs font-bold">
                            {{ substr(Auth::user()->active_pseudo_name ?? Auth::user()->name, 0, 2) }}
                        </div>
                        <span class="text-sm text-gray-300 hidden sm:block">{{ Auth::user()->active_pseudo_name ?? Auth::user()->name }}</span>
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    
                    <div x-show="open" @click.away="open = false" x-transition 
                         class="absolute right-0 mt-2 w-48 bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl shadow-xl py-1 z-50">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-white/5 hover:text-[#D4AF37]">
                            Profile Settings
                        </a>
                        <div class="border-t border-[#2A2A2A] my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-red-500/10">
                                Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 flex overflow-hidden">
            @yield('content')
        </main>
    </div>

    <script>
        // Check for Reverb client availability and subscribe
        const checkReverbInterval = setInterval(() => {
            if (window.reverbClient) {
                clearInterval(checkReverbInterval);
                console.log('Reverb client found, subscribing to notifications...');
                
                // Request notification permission
                if ('Notification' in window && Notification.permission === 'default') {
                    Notification.requestPermission();
                }
                
                // Subscribe to monitoring channel for new visitors
                const monitoringChannel = window.reverbClient.subscribe('monitoring');
                monitoringChannel.bind('visitor.status.changed', function(data) {
                    if (data.is_online) {
                        showNotification('Visitor Online', 'A visitor is now online', '{{ route("admin.visitors.index") }}');
                    }
                });
                monitoringChannel.bind('visitor.joined', function(data) {
                    showNotification(
                        'New Visitor 🔔', 
                        `New visitor from ${data.visitor.location.country || 'Unknown'}`, 
                        '{{ route("admin.visitors.index") }}?session=' + (data.session ? data.session.id : '')
                    );
                });

                // Subscribe to agent private channel for notifications
                @auth
                const userId = {{ Auth::id() }};
                console.log('Subscribing to private-agent.' + userId);
                const agentChannel = window.reverbClient.subscribe('private-agent.' + userId);
                
                agentChannel.bind('pusher:subscription_succeeded', function() {
                    console.log('Successfully subscribed to agent notification channel');
                });
                
                agentChannel.bind('agent.notification', function(data) {
                    console.log('Agent notification received:', data);
                    showNotification(data.title, data.body, data.url);
                });
                @endauth
                
                // Helper functions
                window.showNotification = function(title, body, url) {
                    // Play notification sound
                    playNotificationSound();
                    
                    // Show browser notification if permitted
                    if ('Notification' in window && Notification.permission === 'granted') {
                        const notification = new Notification(title, {
                            body: body,
                            icon: '/favicon.ico',
                            badge: '/favicon.ico',
                            tag: 'visiontech-chat',
                        });
                        
                        notification.onclick = function() {
                            window.focus();
                            if (url) window.location.href = url;
                            notification.close();
                        };
                        
                        setTimeout(() => notification.close(), 5000);
                    }
                    
                    // Also show in-page toast
                    showToast(title, body);
                };
                
                window.playNotificationSound = function() {
                    try {
                        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                        const playTone = (freq, startTime, duration) => {
                            const oscillator = audioContext.createOscillator();
                            const gainNode = audioContext.createGain();
                            oscillator.connect(gainNode);
                            gainNode.connect(audioContext.destination);
                            oscillator.frequency.value = freq;
                            oscillator.type = 'sine';
                            gainNode.gain.setValueAtTime(0, audioContext.currentTime + startTime);
                            gainNode.gain.linearRampToValueAtTime(0.15, audioContext.currentTime + startTime + 0.02);
                            gainNode.gain.setValueAtTime(0.15, audioContext.currentTime + startTime + duration - 0.02);
                            gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + startTime + duration);
                            oscillator.start(audioContext.currentTime + startTime);
                            oscillator.stop(audioContext.currentTime + startTime + duration);
                        };
                        playTone(880, 0, 0.12);
                        playTone(1318.5, 0.12, 0.15);
                    } catch (e) {
                        console.log('Could not play notification sound:', e);
                    }
                };
                
                window.showToast = function(title, body) {
                    const toast = document.createElement('div');
                    toast.className = 'fixed bottom-4 right-4 bg-[#1a1a1a] border border-[#333] rounded-lg shadow-xl p-4 max-w-sm z-50 animate-slide-up';
                    toast.innerHTML = `
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-[#fe9e00] rounded-full flex items-center justify-center text-black flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-white text-sm">${title}</p>
                                <p class="text-gray-400 text-xs mt-0.5 truncate">${body}</p>
                            </div>
                            <button onclick="this.parentElement.parentElement.remove()" class="text-gray-500 hover:text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 5000);
                };
            }
        }, 300);
    </script>
    @stack('scripts')
</body>
</html>
