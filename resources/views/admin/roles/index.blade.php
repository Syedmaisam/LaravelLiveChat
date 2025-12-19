@extends('layouts.admin')

@section('title', 'Roles')

@section('actions')
<a href="{{ route('admin.roles.create') }}" class="bg-[#fe9e00] text-black px-4 py-2 rounded text-sm font-medium hover:bg-[#e08e00]">
    + Add Role
</a>
@endsection

@section('content')
<div class="grid grid-cols-3 gap-4">
    @foreach($roles as $role)
    <div class="bg-[#111] border border-[#222] rounded-lg p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-lg">{{ ucfirst($role->name) }}</h3>
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.roles.edit', $role) }}" class="text-[#fe9e00] hover:underline text-sm">Edit</a>
                @if(!in_array($role->name, ['admin', 'manager', 'agent']))
                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline" onsubmit="return confirm('Delete this role?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-400 hover:underline text-sm">Delete</button>
                </form>
                @endif
            </div>
        </div>
        
        <div class="text-sm text-gray-500 mb-3">{{ $role->users_count ?? 0 }} users</div>
        
        <div class="space-y-1">
            <div class="text-xs text-gray-500 uppercase mb-2">Permissions</div>
            @forelse($role->permissions as $permission)
            <div class="text-xs text-gray-400 flex items-center">
                <span class="w-1.5 h-1.5 rounded-full bg-[#fe9e00] mr-2"></span>
                {{ $permission->name }}
            </div>
            @empty
            <div class="text-xs text-gray-500">No permissions assigned</div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>
@endsection
