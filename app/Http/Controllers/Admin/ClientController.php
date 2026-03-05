<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::withCount('agents')->get();
        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        $agents = User::with('roles')->get();
        return view('admin.clients.form', compact('agents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'agents' => 'nullable|array',
            // Widget settings
            'widget_color' => 'nullable|string|max:7',
            'widget_icon' => 'nullable|string',
            'widget_icon_url' => 'nullable|url',
            'widget_position' => 'nullable|string',
            'widget_welcome_title' => 'nullable|string|max:255',
            'widget_welcome_message' => 'nullable|string|max:500',
            'widget_agent_name' => 'nullable|string|max:100',
            'widget_agent_avatar' => 'nullable|url',
            'widget_show_branding' => 'nullable|boolean',
            'widget_auto_open' => 'nullable|boolean',
            'widget_auto_open_delay' => 'nullable|integer|min:1|max:60',
        ]);

        $client = Client::create([
            'name' => $validated['name'],
            'domain' => $validated['domain'],
            'widget_key' => Str::uuid(),
            'is_active' => $request->has('is_active'),
            'widget_color' => $validated['widget_color'] ?? '#fe9e00',
            'widget_icon' => $validated['widget_icon'] ?? 'chat',
            'widget_icon_url' => $validated['widget_icon_url'] ?? null,
            'widget_position' => $validated['widget_position'] ?? 'bottom-right',
            'widget_welcome_title' => $validated['widget_welcome_title'] ?? 'Hi there! 👋',
            'widget_welcome_message' => $validated['widget_welcome_message'] ?? 'How can we help you today?',
            'widget_agent_name' => $validated['widget_agent_name'] ?? 'Support Team',
            'widget_agent_avatar' => $validated['widget_agent_avatar'] ?? null,
            'widget_show_branding' => $request->has('widget_show_branding'),
            'widget_auto_open' => $request->has('widget_auto_open'),
            'widget_auto_open_delay' => $validated['widget_auto_open_delay'] ?? 5,
        ]);

        if (!empty($validated['agents'])) {
            $client->agents()->sync($validated['agents']);
        }

        return redirect()->route('admin.clients.index')->with('success', 'Client created.');
    }

    public function edit(Client $client)
    {
        $agents = User::with('roles')->get();
        $client->load('agents');
        return view('admin.clients.form', compact('client', 'agents'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'agents' => 'nullable|array',
            // Widget settings
            'widget_color' => 'nullable|string|max:7',
            'widget_icon' => 'nullable|string',
            'widget_icon_url' => 'nullable|url',
            'widget_position' => 'nullable|string',
            'widget_welcome_title' => 'nullable|string|max:255',
            'widget_welcome_message' => 'nullable|string|max:500',
            'widget_agent_name' => 'nullable|string|max:100',
            'widget_agent_avatar' => 'nullable|url',
            'widget_show_branding' => 'nullable|boolean',
            'widget_auto_open' => 'nullable|boolean',
            'widget_auto_open_delay' => 'nullable|integer|min:1|max:60',
        ]);

        $client->update([
            'name' => $validated['name'],
            'domain' => $validated['domain'],
            'is_active' => $request->has('is_active'),
            'widget_color' => $validated['widget_color'] ?? $client->widget_color,
            'widget_icon' => $validated['widget_icon'] ?? $client->widget_icon,
            'widget_icon_url' => $validated['widget_icon_url'],
            'widget_position' => $validated['widget_position'] ?? $client->widget_position,
            'widget_welcome_title' => $validated['widget_welcome_title'] ?? $client->widget_welcome_title,
            'widget_welcome_message' => $validated['widget_welcome_message'] ?? $client->widget_welcome_message,
            'widget_agent_name' => $validated['widget_agent_name'] ?? $client->widget_agent_name,
            'widget_agent_avatar' => $validated['widget_agent_avatar'],
            'widget_show_branding' => $request->has('widget_show_branding'),
            'widget_auto_open' => $request->has('widget_auto_open'),
            'widget_auto_open_delay' => $validated['widget_auto_open_delay'] ?? $client->widget_auto_open_delay,
        ]);

        $client->agents()->sync($validated['agents'] ?? []);

        return redirect()->route('admin.clients.index')->with('success', 'Client updated.');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('admin.clients.index')->with('success', 'Client deleted.');
    }
}
