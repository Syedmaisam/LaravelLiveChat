@extends('layouts.admin')

@section('title', isset($client) ? 'Edit Client' : 'Create Client')

@section('content')
<div class="max-w-2xl">
    <form action="{{ isset($client) ? route('admin.clients.update', $client) : route('admin.clients.store') }}" method="POST" class="space-y-6">
        @csrf
        @if(isset($client))
            @method('PUT')
        @endif

        <div class="bg-[#111] border border-[#222] rounded-lg p-6 space-y-5">
            <h3 class="font-semibold border-b border-[#222] pb-3 -mt-1">Client Details</h3>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Name *</label>
                    <input type="text" name="name" value="{{ old('name', $client->name ?? '') }}" required
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
                    @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Domain</label>
                    <input type="text" name="domain" value="{{ old('domain', $client->domain ?? '') }}" placeholder="example.com"
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
                </div>
            </div>

            @if(isset($client))
            <div>
                <label class="block text-sm text-gray-400 mb-1">Widget Installation Code</label>
                <div class="bg-[#222] rounded p-3 relative group border border-[#333]">
                    <pre class="text-xs text-gray-300 font-mono whitespace-pre-wrap break-all" id="widget-code">&lt;script&gt;
  window.LIVE_CHAT_API_URL = '{{ url('/api') }}'; 
&lt;/script&gt;
&lt;script src="{{ url('/widget.js') }}"&gt;&lt;/script&gt;
&lt;script&gt;
  if(typeof LiveChatWidget !== 'undefined') {
    LiveChatWidget.init('{{ $client->widget_key }}');
  } else {
    window.addEventListener('load', function() {
       LiveChatWidget.init('{{ $client->widget_key }}');
    });
  }
&lt;/script&gt;</pre>
                    <button type="button" onclick="copyWidgetCode()" 
                        class="absolute top-2 right-2 bg-[#333] hover:bg-[#444] text-gray-300 text-xs px-2 py-1 rounded transition-colors border border-[#444]">
                        Copy Code
                    </button>
                    <!-- Confirmation Tooltip -->
                    <span id="copy-feedback" class="absolute top-2 right-20 text-green-500 text-xs hidden transition-opacity">Copied!</span>
                </div>
            </div>
            
            <script>
            function copyWidgetCode() {
                const code = document.getElementById('widget-code').innerText;
                navigator.clipboard.writeText(code).then(() => {
                   const feedback = document.getElementById('copy-feedback');
                   feedback.classList.remove('hidden');
                   setTimeout(() => feedback.classList.add('hidden'), 2000);
                });
            }
            </script>
            @endif

            <div class="flex items-center">
                <input type="checkbox" name="is_active" value="1" id="is_active"
                    {{ old('is_active', $client->is_active ?? true) ? 'checked' : '' }}
                    class="rounded border-[#333] text-[#fe9e00] focus:ring-[#fe9e00]">
                <label for="is_active" class="ml-2 text-sm text-gray-400">Active</label>
            </div>
        </div>

        <div class="bg-[#111] border border-[#222] rounded-lg p-6">
            <h3 class="font-semibold border-b border-[#222] pb-3 mb-4 -mt-1">Assign Agents</h3>
            
            <div class="grid grid-cols-2 gap-2">
                @foreach($agents as $agent)
                <label class="flex items-center p-2 bg-black border border-[#333] rounded cursor-pointer hover:border-[#fe9e00]">
                    <input type="checkbox" name="agents[]" value="{{ $agent->id }}"
                        {{ in_array($agent->id, old('agents', isset($client) ? $client->agents->pluck('id')->toArray() : [])) ? 'checked' : '' }}
                        class="rounded border-[#333] text-[#fe9e00] focus:ring-[#fe9e00]">
                    <span class="ml-2 text-sm">{{ $agent->name }}</span>
                    <span class="ml-auto text-xs text-gray-500">{{ $agent->roles->first()->name ?? '' }}</span>
                </label>
                @endforeach
            </div>
            @if($agents->isEmpty())
            <p class="text-gray-500 text-sm">No agents available. <a href="{{ route('admin.users.create') }}" class="text-[#fe9e00] hover:underline">Create one</a></p>
            @endif
        </div>

        <!-- Widget Customization -->
        <div class="bg-[#111] border border-[#222] rounded-lg p-6 space-y-5">
            <h3 class="font-semibold border-b border-[#222] pb-3 -mt-1">Widget Appearance</h3>
            
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Widget Color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="widget_color" value="{{ old('widget_color', $client->widget_color ?? '#fe9e00') }}"
                            class="w-10 h-10 rounded border border-[#333] cursor-pointer">
                        <input type="text" readonly value="{{ old('widget_color', $client->widget_color ?? '#fe9e00') }}"
                            class="flex-1 bg-black border border-[#333] rounded px-3 py-2 text-sm uppercase"
                            onchange="this.previousElementSibling.value = this.value">
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Widget Icon</label>
                    <select name="widget_icon" class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
                        <option value="chat" {{ old('widget_icon', $client->widget_icon ?? 'chat') === 'chat' ? 'selected' : '' }}>💬 Chat Bubble</option>
                        <option value="support" {{ old('widget_icon', $client->widget_icon ?? '') === 'support' ? 'selected' : '' }}>🎧 Support</option>
                        <option value="message" {{ old('widget_icon', $client->widget_icon ?? '') === 'message' ? 'selected' : '' }}>✉️ Message</option>
                        <option value="help" {{ old('widget_icon', $client->widget_icon ?? '') === 'help' ? 'selected' : '' }}>❓ Help</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Position</label>
                    <select name="widget_position" class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
                        <option value="bottom-right" {{ old('widget_position', $client->widget_position ?? 'bottom-right') === 'bottom-right' ? 'selected' : '' }}>Bottom Right</option>
                        <option value="bottom-left" {{ old('widget_position', $client->widget_position ?? '') === 'bottom-left' ? 'selected' : '' }}>Bottom Left</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-1">Custom Icon URL (optional)</label>
                <input type="url" name="widget_icon_url" value="{{ old('widget_icon_url', $client->widget_icon_url ?? '') }}" placeholder="https://example.com/icon.png"
                    class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
            </div>
        </div>

        <!-- Welcome Message -->
        <div class="bg-[#111] border border-[#222] rounded-lg p-6 space-y-5">
            <h3 class="font-semibold border-b border-[#222] pb-3 -mt-1">Welcome Message</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Agent Name</label>
                    <input type="text" name="widget_agent_name" value="{{ old('widget_agent_name', $client->widget_agent_name ?? 'Support Team') }}"
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Agent Avatar URL</label>
                    <input type="url" name="widget_agent_avatar" value="{{ old('widget_agent_avatar', $client->widget_agent_avatar ?? '') }}" placeholder="https://..."
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-1">Welcome Title</label>
                <input type="text" name="widget_welcome_title" value="{{ old('widget_welcome_title', $client->widget_welcome_title ?? 'Hi there! 👋') }}"
                    class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-1">Welcome Message</label>
                <textarea name="widget_welcome_message" rows="2"
                    class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none resize-none">{{ old('widget_welcome_message', $client->widget_welcome_message ?? 'How can we help you today?') }}</textarea>
            </div>
        </div>

        <!-- Behavior -->
        <div class="bg-[#111] border border-[#222] rounded-lg p-6 space-y-5">
            <h3 class="font-semibold border-b border-[#222] pb-3 -mt-1">Behavior</h3>
            
            <div class="flex items-center justify-between">
                <div>
                    <label class="text-sm text-white">Show Branding</label>
                    <p class="text-xs text-gray-500">Display "Powered by VisionTech" in widget</p>
                </div>
                <input type="checkbox" name="widget_show_branding" value="1"
                    {{ old('widget_show_branding', $client->widget_show_branding ?? true) ? 'checked' : '' }}
                    class="rounded border-[#333] text-[#fe9e00] focus:ring-[#fe9e00]">
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <label class="text-sm text-white">Auto-Open Widget</label>
                    <p class="text-xs text-gray-500">Automatically open chat window after delay</p>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="widget_auto_open" value="1"
                        {{ old('widget_auto_open', $client->widget_auto_open ?? false) ? 'checked' : '' }}
                        class="rounded border-[#333] text-[#fe9e00] focus:ring-[#fe9e00]">
                    <input type="number" name="widget_auto_open_delay" value="{{ old('widget_auto_open_delay', $client->widget_auto_open_delay ?? 5) }}"
                        min="1" max="60" class="w-16 bg-black border border-[#333] rounded px-2 py-1 text-sm text-center">
                    <span class="text-xs text-gray-500">seconds</span>
                </div>
            </div>
        </div>


        <div class="flex items-center justify-between">
            <a href="{{ route('admin.clients.index') }}" class="text-gray-400 hover:text-white text-sm">← Back to Clients</a>
            <button type="submit" class="bg-[#fe9e00] text-black px-6 py-2 rounded font-medium hover:bg-[#e08e00]">
                {{ isset($client) ? 'Update Client' : 'Create Client' }}
            </button>
        </div>
    </form>
</div>
@endsection
