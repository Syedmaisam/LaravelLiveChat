@extends('layouts.admin')

@section('title', isset($role) ? 'Edit Role' : 'Create Role')

@section('content')
<div class="max-w-2xl">
    <form action="{{ isset($role) ? route('admin.roles.update', $role) : route('admin.roles.store') }}" method="POST" class="space-y-6">
        @csrf
        @if(isset($role))
            @method('PUT')
        @endif

        <div class="bg-[#111] border border-[#222] rounded-lg p-6 space-y-5">
            <div>
                <label class="block text-sm text-gray-400 mb-1">Role Name *</label>
                <input type="text" name="name" value="{{ old('name', $role->name ?? '') }}" required
                    {{ isset($role) && in_array($role->name, ['admin', 'manager', 'agent']) ? 'readonly' : '' }}
                    class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none {{ isset($role) && in_array($role->name, ['admin', 'manager', 'agent']) ? 'opacity-50' : '' }}">
                @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-2">Permissions</label>
                <div class="space-y-4">
                    @php
                        $grouped = $permissions->groupBy(function($p) {
                            return explode('.', $p->name)[0] ?? 'general';
                        });
                    @endphp
                    
                    @foreach($grouped as $group => $perms)
                    <div>
                        <div class="text-xs text-gray-500 uppercase mb-2">{{ ucfirst($group) }}</div>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($perms as $permission)
                            <label class="flex items-center p-2 bg-black border border-[#333] rounded cursor-pointer hover:border-[#fe9e00]">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                    {{ in_array($permission->id, old('permissions', isset($role) ? $role->permissions->pluck('id')->toArray() : [])) ? 'checked' : '' }}
                                    class="rounded border-[#333] text-[#fe9e00] focus:ring-[#fe9e00]">
                                <span class="ml-2 text-sm">{{ $permission->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.roles.index') }}" class="text-gray-400 hover:text-white text-sm">← Back to Roles</a>
            <button type="submit" class="bg-[#fe9e00] text-black px-6 py-2 rounded font-medium hover:bg-[#e08e00]">
                {{ isset($role) ? 'Update Role' : 'Create Role' }}
            </button>
        </div>
    </form>
</div>
@endsection
