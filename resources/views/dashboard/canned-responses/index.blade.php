@extends('layouts.admin')

@section('title', 'Canned Responses')

@section('actions')
<button onclick="document.getElementById('create-modal').classList.remove('hidden')" 
    class="bg-[#fe9e00] text-black px-4 py-2 rounded font-medium hover:bg-[#e08e00] text-sm">
    + New Response
</button>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @forelse($responses as $category => $items)
    <div class="bg-[#111] border border-[#222] rounded-lg">
        <div class="px-5 py-4 border-b border-[#222]">
            <h3 class="font-semibold text-white">{{ $category ?: 'General' }}</h3>
        </div>
        <div class="divide-y divide-[#222]">
            @foreach($items as $response)
            <div class="p-4 hover:bg-[#1a1a1a] group">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <code class="text-[#fe9e00] text-sm bg-[#222] px-1.5 py-0.5 rounded">{{ $response->shortcut }}</code>
                            <span class="font-medium text-white text-sm">{{ $response->title }}</span>
                            @if($response->is_global)
                            <span class="text-[10px] bg-blue-500/20 text-blue-400 px-1.5 py-0.5 rounded">GLOBAL</span>
                            @endif
                        </div>
                        <p class="text-gray-500 text-sm truncate">{{ Str::limit($response->content, 100) }}</p>
                        <div class="text-xs text-gray-600 mt-1">Used {{ $response->usage_count }} times</div>
                    </div>
                    @if(!$response->is_global || auth()->user()->isAdmin())
                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick="editResponse({{ json_encode($response) }})" class="text-gray-400 hover:text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </button>
                        <form action="{{ route('dashboard.canned-responses.destroy', $response) }}" method="POST" class="inline" onsubmit="return confirm('Delete this response?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="col-span-2 bg-[#111] border border-[#222] rounded-lg p-12 text-center">
        <div class="text-gray-500 mb-4">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
            <p class="font-medium">No canned responses yet</p>
            <p class="text-sm mt-1">Create quick replies to speed up your conversations</p>
        </div>
        <button onclick="document.getElementById('create-modal').classList.remove('hidden')" 
            class="bg-[#fe9e00] text-black px-4 py-2 rounded font-medium hover:bg-[#e08e00] text-sm">
            Create Your First Response
        </button>
    </div>
    @endforelse
</div>

<!-- Create Modal -->
<div id="create-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-[#111] border border-[#222] rounded-lg w-full max-w-lg mx-4">
        <div class="px-5 py-4 border-b border-[#222] flex items-center justify-between">
            <h3 class="font-semibold">Create Canned Response</h3>
            <button onclick="document.getElementById('create-modal').classList.add('hidden')" class="text-gray-400 hover:text-white">×</button>
        </div>
        <form action="{{ route('dashboard.canned-responses.store') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5">Shortcut *</label>
                    <input type="text" name="shortcut" placeholder="/greeting" required pattern="^\/[a-z0-9_]+$"
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
                    <p class="text-xs text-gray-600 mt-1">Start with / (e.g., /hello)</p>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5">Category</label>
                    <input type="text" name="category" placeholder="Greeting" 
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1.5">Title *</label>
                <input type="text" name="title" placeholder="Friendly Greeting" required
                    class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1.5">Message Content *</label>
                <textarea name="content" rows="4" placeholder="Hello! How can I help you today?" required
                    class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none resize-none"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" 
                    class="px-4 py-2 text-gray-400 hover:text-white text-sm">Cancel</button>
                <button type="submit" class="bg-[#fe9e00] text-black px-4 py-2 rounded font-medium hover:bg-[#e08e00] text-sm">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-[#111] border border-[#222] rounded-lg w-full max-w-lg mx-4">
        <div class="px-5 py-4 border-b border-[#222] flex items-center justify-between">
            <h3 class="font-semibold">Edit Canned Response</h3>
            <button onclick="document.getElementById('edit-modal').classList.add('hidden')" class="text-gray-400 hover:text-white">×</button>
        </div>
        <form id="edit-form" method="POST" class="p-5 space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5">Shortcut *</label>
                    <input type="text" name="shortcut" id="edit-shortcut" required pattern="^\/[a-z0-9_]+$"
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5">Category</label>
                    <input type="text" name="category" id="edit-category"
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1.5">Title *</label>
                <input type="text" name="title" id="edit-title" required
                    class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1.5">Message Content *</label>
                <textarea name="content" id="edit-content" rows="4" required
                    class="w-full bg-black border border-[#333] rounded px-3 py-2 text-white text-sm focus:border-[#fe9e00] focus:outline-none resize-none"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')" 
                    class="px-4 py-2 text-gray-400 hover:text-white text-sm">Cancel</button>
                <button type="submit" class="bg-[#fe9e00] text-black px-4 py-2 rounded font-medium hover:bg-[#e08e00] text-sm">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function editResponse(response) {
    document.getElementById('edit-form').action = '/dashboard/canned-responses/' + response.id;
    document.getElementById('edit-shortcut').value = response.shortcut;
    document.getElementById('edit-title').value = response.title;
    document.getElementById('edit-content').value = response.content;
    document.getElementById('edit-category').value = response.category || '';
    document.getElementById('edit-modal').classList.remove('hidden');
}
</script>
@endsection
