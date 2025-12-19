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
                });
                console.log('Reverb client initialized');
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
<body class="bg-black text-white min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-56 bg-[#111] border-r border-[#222] flex flex-col fixed h-full">
            <!-- Logo -->
            <div class="h-14 flex items-center px-4 border-b border-[#222]">
                <span class="text-[#fe9e00] font-bold text-lg">VisionTech</span>
            </div>

            <!-- Menu -->
            <nav class="flex-1 py-4 px-3 space-y-1">
                <div class="text-[10px] uppercase tracking-wider text-gray-500 px-3 mb-2">Main</div>
                
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2 rounded text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-[#fe9e00] text-black font-medium' : 'text-gray-400 hover:text-white hover:bg-[#1a1a1a]' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Dashboard
                </a>

                <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2 rounded text-sm text-gray-400 hover:text-white hover:bg-[#1a1a1a]">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Live Chat
                </a>

                <a href="{{ route('admin.visitors.index') }}" class="flex items-center px-3 py-2 rounded text-sm {{ request()->routeIs('admin.visitors.*') ? 'bg-[#fe9e00] text-black font-medium' : 'text-gray-400 hover:text-white hover:bg-[#1a1a1a]' }}">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Visitors
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
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-500 hover:text-[#fe9e00]" title="Logout">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <main class="flex-1 ml-56">
            <!-- Header -->
            <header class="h-14 bg-[#111] border-b border-[#222] flex items-center justify-between px-6 sticky top-0 z-10">
                <h1 class="text-lg font-semibold">@yield('title')</h1>
                @yield('actions')
            </header>

            <!-- Content -->
            <div class="p-6">
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
