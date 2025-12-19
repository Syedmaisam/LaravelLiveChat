@extends('layouts.admin')

@section('title', isset($permission) ? 'Edit Permission' : 'Create Permission')

@section('content')
<div class="max-w-xl">
    <form action="{{ isset($permission) ? route('admin.permissions.update', $permission) : route('admin.permissions.store') }}" method="POST" class="space-y-6">
        @csrf
        @if(isset($permission))
            @method('PUT')
        @endif

        <div class="bg-[#111] border border-[#222] rounded-lg p-6 space-y-5">
            <div>
                <label class="block text-sm text-gray-400 mb-1">Permission Name *</label>
                <input type="text" name="name" value="{{ old('name', $permission->name ?? '') }}" required placeholder="e.g. users.create"
                    class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
                <p class="text-xs text-gray-500 mt-1">Use dot notation for grouping: users.create, users.edit, chats.view</p>
                @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-1">Description</label>
                <input type="text" name="description" value="{{ old('description', $permission->description ?? '') }}" placeholder="What this permission allows"
                    class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.permissions.index') }}" class="text-gray-400 hover:text-white text-sm">← Back</a>
            <button type="submit" class="bg-[#fe9e00] text-black px-6 py-2 rounded font-medium hover:bg-[#e08e00]">
                {{ isset($permission) ? 'Update' : 'Create' }}
            </button>
        </div>
    </form>
</div>
@endsection
