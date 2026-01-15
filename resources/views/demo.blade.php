<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chat Widget Demo - VisionTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-black text-white min-h-screen">
    <!-- Header -->
    <header class="bg-[#111] border-b border-[#222]">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center">
                <img src="{{ asset('visiontechlogow.webp') }}" style="height: 50px;" alt="VisionTech">
                <span class="ml-2 text-gray-500 text-sm">Demo Site</span>
            </div>
            <nav class="space-x-6 text-sm">
                <a href="#" class="text-gray-400 hover:text-white">Home</a>
                <a href="#about" class="text-gray-400 hover:text-white">About</a>
                <a href="#services" class="text-gray-400 hover:text-white">Services</a>
                <a href="#contact" class="text-gray-400 hover:text-white">Contact</a>
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <section class="py-20 px-4">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-5xl font-bold mb-6">Welcome to <span class="text-[#fe9e00]">VisionTech</span></h1>
            <p class="text-xl text-gray-400 mb-8">This is a demo page to test the live chat widget. Click the chat button in the bottom right corner to start a conversation!</p>
            <div class="flex justify-center gap-4">
                <a href="{{ url('/admin') }}" class="bg-[#fe9e00] text-black font-medium px-6 py-3 rounded hover:bg-[#e08e00]">Open Admin Panel</a>
                <a href="{{ url('/dashboard') }}" class="border border-[#333] text-white font-medium px-6 py-3 rounded hover:border-[#fe9e00]">Open Dashboard</a>
            </div>
        </div>
    </section>

    <!-- About -->
    <section id="about" class="py-16 px-4 bg-[#111]">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-3xl font-bold mb-6 text-center">About Us</h2>
            <p class="text-gray-400 text-center max-w-2xl mx-auto">
                VisionTech provides cutting-edge live chat solutions for businesses. Our platform enables real-time customer support, visitor monitoring, and seamless communication between your team and customers.
            </p>
        </div>
    </section>

    <!-- Services -->
    <section id="services" class="py-16 px-4">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-3xl font-bold mb-10 text-center">Our Services</h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-[#111] border border-[#222] rounded-lg p-6 text-center">
                    <div class="w-12 h-12 bg-[#fe9e00]/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-[#fe9e00]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <h3 class="font-semibold mb-2">Live Chat</h3>
                    <p class="text-gray-500 text-sm">Real-time messaging with your customers</p>
                </div>
                <div class="bg-[#111] border border-[#222] rounded-lg p-6 text-center">
                    <div class="w-12 h-12 bg-[#fe9e00]/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-[#fe9e00]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </div>
                    <h3 class="font-semibold mb-2">Visitor Monitoring</h3>
                    <p class="text-gray-500 text-sm">Track visitors in real-time</p>
                </div>
                <div class="bg-[#111] border border-[#222] rounded-lg p-6 text-center">
                    <div class="w-12 h-12 bg-[#fe9e00]/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-[#fe9e00]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <h3 class="font-semibold mb-2">Analytics</h3>
                    <p class="text-gray-500 text-sm">Detailed reports and insights</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact -->
    <section id="contact" class="py-16 px-4 bg-[#111]">
        <div class="max-w-xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-6">Get In Touch</h2>
            <p class="text-gray-400 mb-8">Have questions? Use the chat widget in the bottom right corner to talk to us, or fill out the form below.</p>
            <form class="space-y-4">
                <input type="text" placeholder="Your Name" class="w-full bg-black border border-[#333] rounded px-4 py-3 text-white focus:border-[#fe9e00] focus:outline-none">
                <input type="email" placeholder="Your Email" class="w-full bg-black border border-[#333] rounded px-4 py-3 text-white focus:border-[#fe9e00] focus:outline-none">
                <textarea placeholder="Your Message" rows="4" class="w-full bg-black border border-[#333] rounded px-4 py-3 text-white focus:border-[#fe9e00] focus:outline-none"></textarea>
                <button type="submit" class="w-full bg-[#fe9e00] text-black font-medium py-3 rounded hover:bg-[#e08e00]">Send Message</button>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-6 px-4 border-t border-[#222] text-center text-gray-500 text-sm">
        &copy; 2024 VisionTech. This is a demo page to test the chat widget.
    </footer>

    <!-- Chat Widget -->
    <!-- Widget -->
    @php
        $client = \App\Models\Client::where('is_active', true)->first();
        $widgetKey = $client?->widget_key ?? 'demo';
    @endphp
    <script>
        window.LIVE_CHAT_API_URL = '{{ url('/api') }}';
    </script>
    <script src="{{ url('/widget.js') }}?v={{ time() }}"></script>
    <script>
        // Initialize widget
        window.addEventListener('load', function() {
            if (typeof LiveChatWidget !== 'undefined') {
                LiveChatWidget.init('{{ $widgetKey }}');
            }
        });
    </script>
</body>
</html>
