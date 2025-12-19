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
        ]);

        $client = Client::create([
            'name' => $validated['name'],
            'domain' => $validated['domain'],
            'widget_key' => Str::uuid(),
            'is_active' => $request->has('is_active'),
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
        ]);

        $client->update([
            'name' => $validated['name'],
            'domain' => $validated['domain'],
            'is_active' => $request->has('is_active'),
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
