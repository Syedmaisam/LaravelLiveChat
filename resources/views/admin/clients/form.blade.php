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
                <label class="block text-sm text-gray-400 mb-1">Widget Key</label>
                <div class="flex items-center space-x-2">
                    <code class="flex-1 bg-[#222] px-3 py-2 rounded text-sm text-[#fe9e00]">{{ $client->widget_key }}</code>
                    <button type="button" onclick="navigator.clipboard.writeText('{{ $client->widget_key }}')" class="px-3 py-2 bg-[#222] hover:bg-[#333] rounded text-sm">Copy</button>
                </div>
            </div>
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

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.clients.index') }}" class="text-gray-400 hover:text-white text-sm">← Back to Clients</a>
            <button type="submit" class="bg-[#fe9e00] text-black px-6 py-2 rounded font-medium hover:bg-[#e08e00]">
                {{ isset($client) ? 'Update Client' : 'Create Client' }}
            </button>
        </div>
    </form>
</div>
@endsection
