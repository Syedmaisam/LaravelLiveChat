@extends('layouts.admin')

@section('title', isset($user) ? 'Edit User' : 'Create User')

@section('content')
<div class="max-w-2xl">
    <form action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST" class="space-y-6">
        @csrf
        @if(isset($user))
            @method('PUT')
        @endif

        <div class="bg-[#111] border border-[#222] rounded-lg p-6 space-y-5">
            <h3 class="font-semibold border-b border-[#222] pb-3 -mt-1">User Details</h3>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
                    @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
                    @error('email')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Password {{ isset($user) ? '(leave blank to keep)' : '*' }}</label>
                    <input type="password" name="password" {{ isset($user) ? '' : 'required' }}
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
                    @error('password')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation"
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
                </div>
            </div>
        </div>

        <div class="bg-[#111] border border-[#222] rounded-lg p-6 space-y-5">
            <h3 class="font-semibold border-b border-[#222] pb-3 -mt-1">Role & Assignment</h3>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Role *</label>
                    <select name="role" required
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role', isset($user) ? $user->roles->first()?->id : '') == $role->id ? 'selected' : '' }}>
                            {{ ucfirst($role->name) }}
                        </option>
                        @endforeach
                    </select>
                    @error('role')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Manager (for Agents)</label>
                    <select name="manager_id"
                        class="w-full bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
                        <option value="">No Manager</option>
                        @foreach($managers as $manager)
                        <option value="{{ $manager->id }}" {{ old('manager_id', $user->manager_id ?? '') == $manager->id ? 'selected' : '' }}>
                            {{ $manager->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-2">Assign to Clients</label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach($clients as $client)
                    <label class="flex items-center p-2 bg-black border border-[#333] rounded cursor-pointer hover:border-[#fe9e00]">
                        <input type="checkbox" name="clients[]" value="{{ $client->id }}"
                            {{ in_array($client->id, old('clients', isset($user) ? $user->clients->pluck('id')->toArray() : [])) ? 'checked' : '' }}
                            class="rounded border-[#333] text-[#fe9e00] focus:ring-[#fe9e00]">
                        <span class="ml-2 text-sm">{{ $client->name }}</span>
                    </label>
                    @endforeach
                </div>
                @if($clients->isEmpty())
                <p class="text-gray-500 text-sm">No clients available. <a href="{{ route('admin.clients.create') }}" class="text-[#fe9e00] hover:underline">Create one</a></p>
                @endif
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-white text-sm">← Back to Users</a>
            <button type="submit" class="bg-[#fe9e00] text-black px-6 py-2 rounded font-medium hover:bg-[#e08e00]">
                {{ isset($user) ? 'Update User' : 'Create User' }}
            </button>
        </div>
    </form>
</div>
@endsection
