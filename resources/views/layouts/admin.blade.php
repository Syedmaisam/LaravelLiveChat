<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - VisionTech Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <meta name="reverb-app-key" content="{{ config('broadcasting.connections.reverb.key') }}">
    <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host') }}">
    <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.options.port') }}">
    <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.options.scheme') }}">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        orange: '#fe9e00'
                    }
                }
            }
        }
        
        // Initialize Pusher/Reverb client
        document.addEventListener('DOMContentLoaded', function() {
            const appKey = document.querySelector('meta[name="reverb-app-key"]')?.content;
            const host = document.querySelector('meta[name="reverb-host"]')?.content || window.location.hostname;
            const port = document.querySelector('meta[name="reverb-port"]')?.content || 8080;
            const scheme = document.querySelector('meta[name="reverb-scheme"]')?.content || 'http';
            
            if (appKey) {
                window.reverbClient = new Pusher(appKey, {
                    wsHost: host,
                    wsPort: port,
                    wssPort: port,
                    forceTLS: scheme === 'https',
                    enabledTransports: ['ws', 'wss'],
                    cluster: 'mt1',
                    authEndpoint: '/broadcasting/auth',
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    }
                });
                console.log('Reverb client initialized');
                
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
                const userId = {{ Auth::id() }};
                console.log('Subscribing to private-agent.' + userId);
                const agentChannel = window.reverbClient.subscribe('private-agent.' + userId);
                
                agentChannel.bind('pusher:subscription_succeeded', function() {
                    console.log('Successfully subscribed to agent notification channel');
                });
                
                agentChannel.bind('pusher:subscription_error', function(error) {
                    console.error('Failed to subscribe to agent channel:', error);
                });
                
                agentChannel.bind('agent.notification', function(data) {
                    console.log('Agent notification received:', data);
                    showNotification(data.title, data.body, data.url);
                });
                
                // Subscribe to all chat channels (will be overridden in specific chat views)
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
                            requireInteraction: false,
                        });
                        
                        notification.onclick = function() {
                            window.focus();
                            if (url) window.location.href = url;
                            notification.close();
                        };
                        
                        // Auto-close after 5 seconds
                        setTimeout(() => notification.close(), 5000);
                    }
                    
                    // Also show in-page toast
                    showToast(title, body);
                };
                
                window.playNotificationSound = function() {
                    // Create a pleasant two-tone notification sound
                    try {
                        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                        
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
                        
                        // Play a pleasant "ding-ding" sound
                        playTone(880, 0, 0.12);      // A5
                        playTone(1318.5, 0.12, 0.15); // E6 (higher note)
                        
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
                    
                    // Auto-remove after 5 seconds
                    setTimeout(() => toast.remove(), 5000);
                };
            }
        });
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { font-family: 'Inter', sans-serif; }
        :root { --orange: #fe9e00; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-black text-white min-h-screen" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">
        <!-- Mobile Menu Button -->
        <button @click="sidebarOpen = true" 
                class="lg:hidden fixed top-3 left-3 z-50 p-2 rounded-lg bg-[#111] border border-[#222] text-white hover:bg-[#1a1a1a]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        
        <!-- Sidebar Overlay (Mobile) -->
        <div x-show="sidebarOpen" 
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/50 z-40 lg:hidden"
             x-cloak></div>
        
        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="w-56 bg-[#111] border-r border-[#222] flex flex-col fixed h-full z-50 transition-transform duration-300 ease-in-out">
            <!-- Logo with Close Button -->
            <div class="h-14 flex items-center justify-between px-4 border-b border-[#222]">
                <a href="{{ route('admin.dashboard') }}" class="block transition-opacity hover:opacity-80">
                    <img src="{{ asset('logo.svg') }}" alt="{{ config('app.name') }}" class="h-8">
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Menu -->
            <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
                <div class="text-[10px] uppercase tracking-wider text-gray-500 px-3 mb-2">Main</div>
                
                <a href="{{ route('admin.dashboard') }}" @click="sidebarOpen = false" class="flex items-center px-3 py-2 rounded text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-[#fe9e00] text-black font-medium' : 'text-gray-400 hover:text-white hover:bg-[#1a1a1a]' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Dashboard
                </a>

                <a href="{{ route('dashboard') }}" @click="sidebarOpen = false" class="flex items-center px-3 py-2 rounded text-sm text-gray-400 hover:text-white hover:bg-[#1a1a1a]">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Live Chat
                </a>

                <a href="{{ route('admin.visitors.index') }}" class="flex items-center px-3 py-2 rounded text-sm {{ request()->routeIs('admin.visitors.*') ? 'bg-[#fe9e00] text-black font-medium' : 'text-gray-400 hover:text-white hover:bg-[#1a1a1a]' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Visitors
                </a>

                <a href="{{ route('dashboard.canned-responses.index') }}" class="flex items-center px-3 py-2 rounded text-sm {{ request()->routeIs('dashboard.canned-responses.*') ? 'bg-[#fe9e00] text-black font-medium' : 'text-gray-400 hover:text-white hover:bg-[#1a1a1a]' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    Canned Responses
                </a>

                <a href="{{ route('admin.auto-greetings.index') }}" class="flex items-center px-3 py-2 rounded text-sm {{ request()->routeIs('admin.auto-greetings.*') ? 'bg-[#fe9e00] text-black font-medium' : 'text-gray-400 hover:text-white hover:bg-[#1a1a1a]' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    Auto-Greetings
                </a>

                <div class="text-[10px] uppercase tracking-wider text-gray-500 px-3 mt-6 mb-2">Management</div>

                <a href="{{ route('admin.users.index') }}" class="flex items-center px-3 py-2 rounded text-sm {{ request()->routeIs('admin.users.*') ? 'bg-[#fe9e00] text-black font-medium' : 'text-gray-400 hover:text-white hover:bg-[#1a1a1a]' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Users
                </a>

                <a href="{{ route('admin.roles.index') }}" class="flex items-center px-3 py-2 rounded text-sm {{ request()->routeIs('admin.roles.*') ? 'bg-[#fe9e00] text-black font-medium' : 'text-gray-400 hover:text-white hover:bg-[#1a1a1a]' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Roles
                </a>

                <a href="{{ route('admin.permissions.index') }}" class="flex items-center px-3 py-2 rounded text-sm {{ request()->routeIs('admin.permissions.*') ? 'bg-[#fe9e00] text-black font-medium' : 'text-gray-400 hover:text-white hover:bg-[#1a1a1a]' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    Permissions
                </a>

                <a href="{{ route('admin.clients.index') }}" class="flex items-center px-3 py-2 rounded text-sm {{ request()->routeIs('admin.clients.*') ? 'bg-[#fe9e00] text-black font-medium' : 'text-gray-400 hover:text-white hover:bg-[#1a1a1a]' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Clients
                </a>
            </nav>

            <!-- User -->
            <div class="p-4 border-t border-[#222]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded bg-[#fe9e00] flex items-center justify-center text-black text-xs font-bold">
                            {{ substr(Auth::user()->name, 0, 2) }}
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-gray-500">{{ Auth::user()->roles->first()->name ?? 'User' }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('profile.edit') }}" class="text-gray-500 hover:text-[#fe9e00]" title="Profile Settings">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-500 hover:text-[#fe9e00]" title="Logout">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <main class="flex-1 ml-0 lg:ml-56 min-h-screen">
            <!-- Header -->
            <header class="h-14 bg-[#111] border-b border-[#222] flex items-center justify-between px-4 lg:px-6 sticky top-0 z-10">
                <h1 class="text-lg font-semibold ml-10 lg:ml-0">@yield('title')</h1>
                @yield('actions')
            </header>

            <!-- Content -->
            <div class="p-4 lg:p-6">
                @if(session('success'))
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded mb-6 text-sm">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded mb-6 text-sm">
                    {{ session('error') }}
                </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
