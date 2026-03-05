@extends('layouts.admin')

@section('title', 'Profile Settings')

@section('content')
<div class="max-w-4xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Profile Settings</h1>
        <p class="text-gray-400 mt-1">Manage your display names and preferences</p>
    </div>

        @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 px-4 py-3 rounded-lg mb-6 text-sm flex items-center">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Basic Info -->
            <div class="bg-[#1A1A1A] rounded-xl border border-[#2A2A2A] p-5">
                <h3 class="text-sm font-semibold text-white mb-4">Basic Information</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">Full Name</label>
                        <input type="text" name="name" value="{{ $user->name }}" required
                            class="w-full bg-[#252525] border border-[#333] rounded-lg px-3 py-2 text-sm text-white focus:ring-1 focus:ring-[#D4AF37] focus:border-[#D4AF37]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">Email</label>
                        <input type="email" value="{{ $user->email }}" disabled
                            class="w-full bg-[#1A1A1A] border border-[#252525] rounded-lg px-3 py-2 text-sm text-gray-500">
                    </div>
                </div>
            </div>

            <!-- Pseudo Names -->
            <div class="bg-[#1A1A1A] rounded-xl border border-[#2A2A2A] p-5" x-data="{
                names: {{ json_encode($user->pseudo_names ?? []) }},
                activeName: '{{ $user->active_pseudo_name ?? '' }}',
                activeIndex: null,
                init() {
                    // Find index of active name, default to 0 if exists but not found
                    this.activeIndex = this.names.indexOf(this.activeName);
                    if (this.activeIndex === -1 && this.names.length > 0) this.activeIndex = 0;
                    
                    // Watch for changes to ensure activeName input stays synced
                    this.$watch('activeIndex', val => {
                        if (val !== null && this.names[val]) {
                            this.activeName = this.names[val];
                        }
                    });
                },
                addName() { 
                    if (this.names.length < 5) {
                        this.names.push('');
                        // If this is the first name, make it active
                        if (this.names.length === 1) this.activeIndex = 0;
                    } 
                },
                removeName(i) { 
                    this.names.splice(i, 1);
                    // Adjust active index if needed
                    if (this.activeIndex === i) {
                        this.activeIndex = this.names.length > 0 ? 0 : null;
                    } else if (this.activeIndex > i) {
                        this.activeIndex--;
                    }
                }
            }" x-init="init()">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-white">Display Names</h3>
                        <p class="text-xs text-gray-500">Names shown to visitors when you chat</p>
                    </div>
                    <button type="button" @click="addName()" x-show="names.length < 5" 
                        class="text-[#D4AF37] hover:text-[#F4D03F] text-xs font-medium flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add
                    </button>
                </div>

                <!-- Hidden input for the actual active name value -->
                <input type="hidden" name="active_pseudo_name" :value="names[activeIndex] || ''">

                <div class="space-y-2">
                    <template x-for="(name, index) in names" :key="index">
                        <div class="flex items-center space-x-2">
                            <input type="text" :name="`pseudo_names[${index}]`" x-model="names[index]" placeholder="Display name..." required
                                class="flex-1 bg-[#252525] border border-[#333] rounded-lg px-3 py-2 text-sm text-white focus:ring-1 focus:ring-[#D4AF37] focus:border-[#D4AF37]">
                            
                            <label class="flex items-center space-x-1.5 px-3 py-2 bg-[#252525] rounded-lg cursor-pointer hover:bg-[#333] transition-colors"
                                :class="{'ring-1 ring-[#D4AF37] bg-[#333]': activeIndex === index}">
                                <input type="radio" name="pseudo_name_selection" :value="index" x-model="activeIndex" class="text-[#D4AF37] focus:ring-[#D4AF37]">
                                <span class="text-xs text-gray-400" :class="{'text-[#D4AF37] font-medium': activeIndex === index}">Active</span>
                            </label>
                            
                            <button type="button" @click="removeName(index)" class="p-2 text-gray-500 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <template x-if="names.length === 0">
                    <div class="text-center py-6 border border-dashed border-[#333] rounded-lg">
                        <p class="text-sm text-gray-500 mb-2">No display names</p>
                        <button type="button" @click="addName()" class="text-[#D4AF37] text-xs font-medium">+ Add one</button>
                    </div>
                </template>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2 bg-gradient-to-r from-[#D4AF37] to-[#B8860B] text-black font-medium rounded-lg hover:opacity-90 transition-opacity text-sm">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
