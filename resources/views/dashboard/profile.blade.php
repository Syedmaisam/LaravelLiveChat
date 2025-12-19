@extends('layouts.admin')

@section('title', 'Profile Settings')

@section('content')
<div class="max-w-2xl">
    <div class="bg-[#111] border border-[#222] rounded-lg p-6">
        <h2 class="text-lg font-semibold mb-4">Pseudo Names / Nicknames</h2>
        <p class="text-gray-500 text-sm mb-6">Manage the names visitors see when you chat with them. You can have multiple nicknames and select which one to use.</p>
        
        <!-- Current Active Name -->
        <div class="mb-6">
            <label class="block text-sm text-gray-400 mb-2">Active Display Name</label>
            <form action="{{ route('profile.update.nickname') }}" method="POST" class="flex gap-3">
                @csrf
                <select name="active_pseudo_name" class="flex-1 bg-black border border-[#333] rounded px-4 py-2 text-white focus:border-[#fe9e00] focus:outline-none">
                    <option value="">Use Real Name ({{ auth()->user()->name }})</option>
                    @foreach(auth()->user()->pseudo_names ?? [] as $name)
                    <option value="{{ $name }}" {{ auth()->user()->active_pseudo_name === $name ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-[#fe9e00] text-black px-4 py-2 rounded font-medium hover:bg-[#e08e00]">Save</button>
            </form>
        </div>
        
        <!-- Add New Nickname -->
        <div class="mb-6">
            <label class="block text-sm text-gray-400 mb-2">Add New Nickname</label>
            <form action="{{ route('profile.add.nickname') }}" method="POST" class="flex gap-3">
                @csrf
                <input type="text" name="nickname" placeholder="Enter nickname..." required
                    class="flex-1 bg-black border border-[#333] rounded px-4 py-2 text-white focus:border-[#fe9e00] focus:outline-none">
                <button type="submit" class="bg-[#222] text-white px-4 py-2 rounded font-medium hover:bg-[#333]">Add</button>
            </form>
        </div>
        
        <!-- Existing Nicknames -->
        @if(count(auth()->user()->pseudo_names ?? []) > 0)
        <div>
            <label class="block text-sm text-gray-400 mb-2">Your Nicknames</label>
            <div class="space-y-2">
                @foreach(auth()->user()->pseudo_names ?? [] as $name)
                <div class="flex items-center justify-between bg-[#0a0a0a] border border-[#222] rounded px-4 py-2">
                    <div class="flex items-center gap-2">
                        <span>{{ $name }}</span>
                        @if(auth()->user()->active_pseudo_name === $name)
                        <span class="text-xs bg-[#fe9e00] text-black px-2 py-0.5 rounded">Active</span>
                        @endif
                    </div>
                    <form action="{{ route('profile.remove.nickname') }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="nickname" value="{{ $name }}">
                        <button type="submit" class="text-red-400 hover:text-red-300 text-sm">Remove</button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
