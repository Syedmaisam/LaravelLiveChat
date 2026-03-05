@extends('layouts.admin')

@section('title', 'Auto-Greetings')

@section('actions')
<button onclick="document.getElementById('create-modal').classList.remove('hidden')" 
    class="bg-[#fe9e00] text-black px-4 py-2 rounded font-medium hover:bg-[#e08e00] text-sm">
    + New Trigger
</button>
@endsection

@section('content')
<div class="bg-[#111] border border-[#222] rounded-lg overflow-hidden">
    <table class="w-full">
        <thead class="bg-[#0a0a0a] border-b border-[#222]">
            <tr>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase w-8">Active</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Name</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Trigger</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Stats</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase w-24">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#222]">
            @forelse($greetings as $greeting)
            <tr class="hover:bg-[#1a1a1a]">
                <td class="px-4 py-3">
                    <button onclick="toggleGreeting({{ $greeting->id }})" 
                        class="w-10 h-5 rounded-full relative {{ $greeting->is_active ? 'bg-[#fe9e00]' : 'bg-[#333]' }} transition-colors"
                        id="toggle-{{ $greeting->id }}">
                        <span class="absolute w-4 h-4 rounded-full bg-white top-0.5 transition-transform {{ $greeting->is_active ? 'right-0.5' : 'left-0.5' }}"></span>
                    </button>
                </td>
                <td class="px-4 py-3">
                    <div class="font-medium text-white text-sm">{{ $greeting->name }}</div>
                    <div class="text-xs text-gray-500 truncate max-w-xs">{{ Str::limit($greeting->message, 50) }}</div>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-[#222] text-gray-400 text-xs rounded">{{ str_replace('_', ' ', ucfirst($greeting->trigger_type)) }}</span>
                        <span class="text-xs text-gray-500">
                            @if($greeting->trigger_type === 'time_on_page')
                            {{ $greeting->trigger_conditions['seconds'] ?? 0 }}s
                            @elseif($greeting->trigger_type === 'page_url')
                            "{{ $greeting->trigger_conditions['url_contains'] ?? '' }}"
                            @elseif($greeting->trigger_type === 'scroll_depth')
                            {{ $greeting->trigger_conditions['percentage'] ?? 0 }}%
                            @else
                            enabled
                            @endif
                        </span>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-gray-400">{{ $greeting->client->name }}</td>
                <td class="px-4 py-3">
                    <div class="text-xs">
                        <span class="text-gray-400">{{ $greeting->shown_count }} shown</span>
                        <span class="text-gray-600 mx-1">•</span>
                        <span class="text-gray-400">{{ $greeting->clicked_count }} clicked</span>
                        <span class="text-gray-600 mx-1">•</span>
                        <span class="text-[#fe9e00]">{{ $greeting->conversion_rate }}%</span>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <button onclick="editGreeting({{ json_encode($greeting) }})" class="text-gray-400 hover:text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </button>
                        <form action="{{ route('admin.auto-greetings.destroy', $greeting) }}" method="POST" class="inline" onsubmit="return confirm('Delete this trigger?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                    <p class="mb-2">No auto-greetings configured</p>
                    <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="text-[#fe9e00] hover:underline text-sm">Create your first trigger</button>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Create Modal -->
<div id="create-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-[#111] border border-[#222] rounded-lg w-full max-w-lg mx-4">
        <div class="px-5 py-4 border-b border-[#222] flex items-center justify-between">
            <h3 class="font-semibold">Create Auto-Greeting</h3>
            <button onclick="document.getElementById('create-modal').classList.add('hidden')" class="text-gray-400 hover:text-white">×</button>
        </div>
        <form action="{{ route('admin.auto-greetings.store') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5">Name *</label>
                    <input type="text" name="name" placeholder="Welcome Message" required
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5">Client *</label>
                    <select name="client_id" required class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1.5">Message *</label>
                <textarea name="message" rows="3" placeholder="Hi! Can I help you find something?" required
                    class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none resize-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5">Trigger Type *</label>
                    <select name="trigger_type" id="create-trigger-type" onchange="updateTriggerLabel()" required
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
                        <option value="time_on_page">Time on Page</option>
                        <option value="page_url">Page URL Contains</option>
                        <option value="scroll_depth">Scroll Depth</option>
                        <option value="exit_intent">Exit Intent</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5" id="trigger-value-label">Seconds</label>
                    <input type="text" name="trigger_value" id="create-trigger-value" placeholder="30" required
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5">Delay (sec)</label>
                    <input type="number" name="delay_seconds" value="0" min="0" max="300"
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5">Cooldown (hrs)</label>
                    <input type="number" name="cooldown_hours" value="24" min="1" max="168"
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5">Priority</label>
                    <input type="number" name="priority" value="0" min="0" max="100"
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" 
                    class="px-4 py-2 text-gray-400 hover:text-white text-sm">Cancel</button>
                <button type="submit" class="bg-[#fe9e00] text-black px-4 py-2 rounded font-medium hover:bg-[#e08e00] text-sm">Create</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleGreeting(id) {
    fetch(`/admin/auto-greetings/${id}/toggle`, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }})
        .then(r => r.json())
        .then(data => {
            const btn = document.getElementById(`toggle-${id}`);
            btn.className = `w-10 h-5 rounded-full relative ${data.is_active ? 'bg-[#fe9e00]' : 'bg-[#333]'} transition-colors`;
        });
}

function updateTriggerLabel() {
    const type = document.getElementById('create-trigger-type').value;
    const label = document.getElementById('trigger-value-label');
    const input = document.getElementById('create-trigger-value');
    
    switch(type) {
        case 'time_on_page':
            label.textContent = 'Seconds';
            input.placeholder = '30';
            break;
        case 'page_url':
            label.textContent = 'URL Contains';
            input.placeholder = '/pricing';
            break;
        case 'scroll_depth':
            label.textContent = 'Percentage';
            input.placeholder = '50';
            break;
        case 'exit_intent':
            label.textContent = 'Value (any)';
            input.placeholder = '1';
            break;
    }
}

function editGreeting(greeting) {
    // For simplicity, redirect to edit mode or show edit modal
    alert('Edit greeting: ' + greeting.name);
}
</script>
@endsection
