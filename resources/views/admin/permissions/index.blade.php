@extends('layouts.admin')

@section('title', 'Permissions')

@section('actions')
<a href="{{ route('admin.permissions.create') }}" class="bg-[#fe9e00] text-black px-4 py-2 rounded text-sm font-medium hover:bg-[#e08e00]">
    + Add Permission
</a>
@endsection

@section('content')
<div class="bg-[#111] border border-[#222] rounded-lg overflow-hidden">
    <table class="w-full">
        <thead class="bg-[#0a0a0a] border-b border-[#222]">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Permission</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Description</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Roles</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#222]">
            @forelse($permissions as $permission)
            <tr class="hover:bg-[#1a1a1a]">
                <td class="px-5 py-4">
                    <span class="font-medium text-sm">{{ $permission->name }}</span>
                </td>
                <td class="px-5 py-4 text-sm text-gray-400">
                    {{ $permission->description ?? '-' }}
                </td>
                <td class="px-5 py-4">
                    <div class="flex flex-wrap gap-1">
                        @foreach($permission->roles as $role)
                        <span class="px-2 py-0.5 rounded text-xs bg-[#222] text-gray-400">{{ $role->name }}</span>
                        @endforeach
                    </div>
                </td>
                <td class="px-5 py-4 text-right">
                    <a href="{{ route('admin.permissions.edit', $permission) }}" class="text-[#fe9e00] hover:underline text-sm mr-3">Edit</a>
                    <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" class="inline" onsubmit="return confirm('Delete this permission?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-400 hover:underline text-sm">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-5 py-12 text-center text-gray-500">
                    No permissions found. <a href="{{ route('admin.permissions.create') }}" class="text-[#fe9e00] hover:underline">Create one</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
