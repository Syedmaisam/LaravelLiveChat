@extends('layouts.admin')

@section('title', 'Users')

@section('actions')
<a href="{{ route('admin.users.create') }}" class="bg-[#fe9e00] text-black px-4 py-2 rounded text-sm font-medium hover:bg-[#e08e00]">
    + Add User
</a>
@endsection

@section('content')
<div class="bg-[#111] border border-[#222] rounded-lg overflow-hidden">
    <table class="w-full">
        <thead class="bg-[#0a0a0a] border-b border-[#222]">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">User</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Role</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Manager</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Clients</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#222]">
            @forelse($users as $user)
            <tr class="hover:bg-[#1a1a1a]">
                <td class="px-5 py-4">
                    <div class="flex items-center">
                        <div class="w-9 h-9 rounded bg-[#222] flex items-center justify-center text-xs font-bold text-[#fe9e00]">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div class="ml-3">
                            <div class="font-medium text-sm">{{ $user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $user->email }}</div>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-4">
                    @foreach($user->roles as $role)
                    <span class="px-2 py-0.5 rounded text-xs font-medium 
                        {{ $role->name === 'admin' ? 'bg-purple-500/10 text-purple-400' : '' }}
                        {{ $role->name === 'manager' ? 'bg-blue-500/10 text-blue-400' : '' }}
                        {{ $role->name === 'agent' ? 'bg-gray-500/10 text-gray-400' : '' }}">
                        {{ ucfirst($role->name) }}
                    </span>
                    @endforeach
                </td>
                <td class="px-5 py-4 text-sm text-gray-400">
                    {{ $user->manager->name ?? '-' }}
                </td>
                <td class="px-5 py-4 text-sm text-gray-400">
                    {{ $user->clients->count() }} assigned
                </td>
                <td class="px-5 py-4">
                    <span class="px-2 py-0.5 rounded text-xs {{ $user->status === 'active' ? 'bg-green-500/10 text-green-400' : 'bg-gray-500/10 text-gray-500' }}">
                        {{ ucfirst($user->status ?? 'active') }}
                    </span>
                </td>
                <td class="px-5 py-4 text-right">
                    <a href="{{ route('admin.users.edit', $user) }}" class="text-[#fe9e00] hover:underline text-sm mr-3">Edit</a>
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Delete this user?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-400 hover:underline text-sm">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-5 py-12 text-center text-gray-500">
                    No users found. <a href="{{ route('admin.users.create') }}" class="text-[#fe9e00] hover:underline">Create one</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
<div class="mt-4">
    {{ $users->links() }}
</div>
@endif
@endsection
