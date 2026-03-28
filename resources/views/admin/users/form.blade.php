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

        <div class="bg-[#111] border border-[#222] rounded-lg p-6 space-y-5" x-data="{
            names: {{ isset($user) ? json_encode($user->pseudo_names ?? []) : '[]' }},
            activeName: '{{ isset($user) ? ($user->active_pseudo_name ?? '') : '' }}',
            activeIndex: null,
            init() {
                this.activeIndex = this.names.indexOf(this.activeName);
                if (this.activeIndex === -1 && this.names.length > 0) this.activeIndex = 0;
                
                this.$watch('activeIndex', val => {
                    if (val !== null && this.names[val]) {
                        this.activeName = this.names[val];
                    }
                });
            },
            addName() { 
                if (this.names.length < 5) {
                    this.names.push('');
                    if (this.names.length === 1) this.activeIndex = 0;
                } 
            },
            removeName(i) { 
                this.names.splice(i, 1);
                if (this.activeIndex === i) {
                    this.activeIndex = this.names.length > 0 ? 0 : null;
                } else if (this.activeIndex > i) {
                    this.activeIndex--;
                }
            }
        }" x-init="init()">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h3 class="font-semibold border-b border-[#222] pb-3 -mt-1">Display Names (Pseudo Names)</h3>
                    <p class="text-xs text-gray-500 mt-1">Names shown to visitors in chat</p>
                </div>
                <button type="button" @click="addName()" x-show="names.length < 5" 
                    class="text-[#fe9e00] hover:text-[#e08e00] text-xs font-medium flex items-center">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add
                </button>
            </div>

            <input type="hidden" name="active_pseudo_name" :value="names[activeIndex] || ''">

            <div class="space-y-2">
                <template x-for="(name, index) in names" :key="index">
                    <div class="flex items-center space-x-2">
                        <input type="text" :name="`pseudo_names[${index}]`" x-model="names[index]" placeholder="Display name..." required
                            class="flex-1 bg-black border border-[#333] rounded px-3 py-2 text-sm focus:border-[#fe9e00] focus:outline-none">
                        
                        <label class="flex items-center space-x-1.5 px-3 py-2 rounded cursor-pointer hover:bg-[#1a1a1a] transition-colors border border-transparent"
                            :class="{'border-[#fe9e00] bg-[#1a1a1a]': activeIndex === index}">
                            <input type="radio" name="pseudo_name_selection" :value="index" x-model="activeIndex" class="text-[#fe9e00] focus:ring-[#fe9e00]">
                            <span class="text-xs text-gray-400" :class="{'text-[#fe9e00] font-medium': activeIndex === index}">Active</span>
                        </label>
                        
                        <button type="button" @click="removeName(index)" class="p-2 text-gray-500 hover:text-red-400 hover:bg-red-500/10 rounded transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            <template x-if="names.length === 0">
                <div class="text-center py-4 border border-dashed border-[#333] rounded">
                    <p class="text-xs text-gray-500 mb-2">No display names</p>
                    <button type="button" @click="addName()" class="text-[#fe9e00] text-xs font-medium">+ Add one</button>
                </div>
            </template>
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
