@extends('layouts.app')

@section('title', 'Client Details')

@section('content')
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">{{ $client->name }}</h1>
            <a href="{{ route('clients.edit', $client) }}"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Edit
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h2 class="text-lg font-semibold mb-4">Client Information</h2>
                <div class="space-y-3">
                    <div>
                        <div class="text-sm text-gray-600">Name</div>
                        <div class="font-medium">{{ $client->name }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600">Domain</div>
                        <div>{{ $client->domain ?? 'Not set' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600">Status</div>
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $client->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $client->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600">Widget Key</div>
                        <code class="text-xs bg-gray-100 px-2 py-1 rounded block">{{ $client->widget_key }}</code>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-lg font-semibold mb-4">Widget Embed Code</h2>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <code class="text-xs block whitespace-pre-wrap">&lt;script&gt;
                        (function() {
                        var script = document.createElement('script');
                        script.src = '{{ url('/widget.js') }}';
                        script.onload = function() {
                        window.LiveChatWidget.init('{{ $client->widget_key }}');
                        };
                        document.head.appendChild(script);
                        })();
                        &lt;/script&gt;</code>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <h2 class="text-lg font-semibold mb-4">Assigned Agents</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($client->agents as $agent)
                    <div class="border rounded-lg p-3">
                        <div class="font-medium">{{ $agent->name }}</div>
                        <div class="text-sm text-gray-600">{{ $agent->email }}</div>
                        <div class="text-xs text-gray-500 mt-1">
                            Pseudo: {{ $agent->pseudo_name ?? 'Not set' }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
