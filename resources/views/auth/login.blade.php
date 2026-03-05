<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - VisionTech</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-black min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm p-6">
        <!-- Logo -->
        <div class="text-center mb-8">
            <img src="{{ asset('visiontechlogow.webp') }}" style="height: 50px;" alt="VisionTech" class="mx-auto mb-4">
            <p class="text-gray-500 text-sm mt-1">Live Chat Admin</p>
        </div>

        <!-- Form -->
        <div class="bg-[#111] border border-[#222] rounded-lg p-6">
            @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded mb-4 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full bg-black border border-[#333] rounded px-3 py-2.5 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Password</label>
                    <input type="password" name="password" required
                        class="w-full bg-black border border-[#333] rounded px-3 py-2.5 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" class="rounded border-[#333] text-[#fe9e00] focus:ring-[#fe9e00]">
                    <label for="remember" class="ml-2 text-sm text-gray-400">Remember me</label>
                </div>
                <button type="submit" class="w-full bg-[#fe9e00] text-black font-medium py-2.5 rounded hover:bg-[#e08e00] transition-colors">
                    Sign In
                </button>
            </form>
        </div>

        <p class="text-center text-gray-600 text-xs mt-6">© {{ date('Y') }} VisionTech</p>
    </div>
</body>
</html>
