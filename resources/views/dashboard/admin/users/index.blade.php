@extends('layouts.dashboard')

@section('title', 'Team Management')

@section('content')
<div class="flex-1 flex flex-col bg-[#0D0D0D] overflow-auto">
    <!-- Header -->
    <div class="bg-[#1A1A1A] border-b border-[#2A2A2A] px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-white">Team Management</h1>
                <p class="text-sm text-gray-500">Manage users, roles and permissions</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="flex items-center space-x-2 bg-gradient-to-r from-[#D4AF37] to-[#B8860B] text-black font-medium py-2 px-4 rounded-lg hover:opacity-90 transition-opacity text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add User</span>
            </a>
        </div>
    </div>

    <div class="p-6">
        <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#252525]">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Manager</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Clients</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#2A2A2A]">
                    @foreach($users as $user)
                    <tr class="hover:bg-white/5">
                        <td class="px-5 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#D4AF37] to-[#B8860B] flex items-center justify-center text-black text-xs font-bold">
                                    {{ substr($user->name, 0, 2) }}
                                </div>
                                <div>
                                    <div class="font-medium text-white text-sm">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            @foreach($user->roles as $role)
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $role->name === 'admin' ? 'bg-purple-500/10 text-purple-400' : ($role->name === 'manager' ? 'bg-blue-500/10 text-blue-400' : 'bg-gray-500/10 text-gray-400') }}">
                                {{ ucfirst($role->name) }}
                            </span>
                            @endforeach
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-400">
                            {{ $user->manager->name ?? '-' }}
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-400">
                            {{ $user->clients->count() }} clients
                        </td>
                        <td class="px-5 py-4">
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-[#D4AF37] hover:underline text-sm">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
