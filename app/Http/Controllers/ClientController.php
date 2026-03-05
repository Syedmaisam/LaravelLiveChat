<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get clients assigned to this user
        $clients = $user->clients()->get();

        return view('clients.index', [
            'clients' => $clients,
        ]);
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);

        $client = Client::create([
            'name' => $request->name,
            'domain' => $request->domain,
            'widget_key' => Str::random(32),
            'widget_settings' => [
                'colors' => [
                    'primary' => '#007bff',
                    'background' => '#ffffff',
                ],
                'position' => 'bottom-right',
                'welcome_text' => 'How can we help you?',
            ],
            'is_active' => true,
        ]);

        // Assign current user to this client
        $client->agents()->attach(Auth::id(), ['assigned_at' => now()]);

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('client-logos', 'public');
            $client->update(['logo' => $logoPath]);
        }

        return redirect()->route('clients.index')->with('success', 'Client created successfully');
    }

    public function show(Client $client)
    {
        $user = Auth::user();

        if (!$user->clients()->where('clients.id', $client->id)->exists()) {
            abort(403);
        }

        return view('clients.show', [
            'client' => $client->load('agents'),
        ]);
    }

    public function edit(Client $client)
    {
        $user = Auth::user();

        if (!$user->clients()->where('clients.id', $client->id)->exists()) {
            abort(403);
        }

        return view('clients.edit', [
            'client' => $client,
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $user = Auth::user();

        if (!$user->clients()->where('clients.id', $client->id)->exists()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        $client->update([
            'name' => $request->name,
            'domain' => $request->domain,
            'is_active' => $request->has('is_active'),
        ]);

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('client-logos', 'public');
            $client->update(['logo' => $logoPath]);
        }

        return redirect()->route('clients.index')->with('success', 'Client updated successfully');
    }

    public function assignAgents(Request $request, Client $client)
    {
        $user = Auth::user();

        if (!$user->clients()->where('clients.id', $client->id)->exists()) {
            abort(403);
        }

        $request->validate([
            'agent_ids' => 'required|array',
            'agent_ids.*' => 'exists:users,id',
        ]);

        $client->agents()->sync($request->agent_ids);

        return redirect()->back()->with('success', 'Agents assigned successfully');
    }
}
