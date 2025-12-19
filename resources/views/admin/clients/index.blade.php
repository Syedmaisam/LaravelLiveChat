@extends('layouts.admin')

@section('title', 'Clients')

@section('actions')
<a href="{{ route('admin.clients.create') }}" class="bg-[#fe9e00] text-black px-4 py-2 rounded text-sm font-medium hover:bg-[#e08e00]">
    + Add Client
</a>
@endsection

@section('content')
<div class="bg-[#111] border border-[#222] rounded-lg overflow-hidden">
    <table class="w-full">
        <thead class="bg-[#0a0a0a] border-b border-[#222]">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Domain</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Widget Key</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Agents</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#222]">
            @forelse($clients as $client)
            <tr class="hover:bg-[#1a1a1a]">
                <td class="px-5 py-4">
                    <span class="font-medium">{{ $client->name }}</span>
                </td>
                <td class="px-5 py-4 text-sm text-gray-400">
                    {{ $client->domain ?? '-' }}
                </td>
                <td class="px-5 py-4">
                    <code class="text-xs bg-[#222] px-2 py-1 rounded text-[#fe9e00]">{{ Str::limit($client->widget_key, 20) }}</code>
                </td>
                <td class="px-5 py-4 text-sm text-gray-400">
                    {{ $client->agents_count ?? $client->agents->count() }} assigned
                </td>
                <td class="px-5 py-4">
                    <span class="px-2 py-0.5 rounded text-xs {{ $client->is_active ? 'bg-green-500/10 text-green-400' : 'bg-gray-500/10 text-gray-500' }}">
                        {{ $client->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-5 py-4 text-right">
                    <a href="{{ route('admin.clients.edit', $client) }}" class="text-[#fe9e00] hover:underline text-sm mr-3">Edit</a>
                    <form action="{{ route('admin.clients.destroy', $client) }}" method="POST" class="inline" onsubmit="return confirm('Delete this client?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-400 hover:underline text-sm">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-5 py-12 text-center text-gray-500">
                    No clients found. <a href="{{ route('admin.clients.create') }}" class="text-[#fe9e00] hover:underline">Create one</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
