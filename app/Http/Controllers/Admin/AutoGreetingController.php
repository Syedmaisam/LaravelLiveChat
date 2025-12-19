<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutoGreeting;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutoGreetingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $clientIds = $user->clients()->pluck('clients.id');
        
        $greetings = AutoGreeting::whereIn('client_id', $clientIds)
            ->with('client')
            ->orderBy('priority', 'desc')
            ->get();

        $clients = $user->clients()->get();

        return view('admin.auto-greetings.index', compact('greetings', 'clients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:100',
            'message' => 'required|string|max:500',
            'trigger_type' => 'required|in:time_on_page,page_url,scroll_depth,exit_intent',
            'trigger_value' => 'required',
            'delay_seconds' => 'nullable|integer|min:0|max:300',
            'cooldown_hours' => 'nullable|integer|min:1|max:168',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);

        // Build trigger conditions based on type
        $conditions = [];
        switch ($validated['trigger_type']) {
            case 'time_on_page':
                $conditions = ['seconds' => (int) $validated['trigger_value']];
                break;
            case 'page_url':
                $conditions = ['url_contains' => $validated['trigger_value']];
                break;
            case 'scroll_depth':
                $conditions = ['percentage' => (int) $validated['trigger_value']];
                break;
            case 'exit_intent':
                $conditions = ['enabled' => true];
                break;
        }

        AutoGreeting::create([
            'client_id' => $validated['client_id'],
            'name' => $validated['name'],
            'message' => $validated['message'],
            'trigger_type' => $validated['trigger_type'],
            'trigger_conditions' => $conditions,
            'delay_seconds' => $validated['delay_seconds'] ?? 0,
            'cooldown_hours' => $validated['cooldown_hours'] ?? 24,
            'priority' => $validated['priority'] ?? 0,
        ]);

        return back()->with('success', 'Auto-greeting created');
    }

    public function update(Request $request, AutoGreeting $autoGreeting)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'message' => 'required|string|max:500',
            'trigger_type' => 'required|in:time_on_page,page_url,scroll_depth,exit_intent',
            'trigger_value' => 'required',
            'delay_seconds' => 'nullable|integer|min:0|max:300',
            'cooldown_hours' => 'nullable|integer|min:1|max:168',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $conditions = [];
        switch ($validated['trigger_type']) {
            case 'time_on_page':
                $conditions = ['seconds' => (int) $validated['trigger_value']];
                break;
            case 'page_url':
                $conditions = ['url_contains' => $validated['trigger_value']];
                break;
            case 'scroll_depth':
                $conditions = ['percentage' => (int) $validated['trigger_value']];
                break;
            case 'exit_intent':
                $conditions = ['enabled' => true];
                break;
        }

        $autoGreeting->update([
            'name' => $validated['name'],
            'message' => $validated['message'],
            'trigger_type' => $validated['trigger_type'],
            'trigger_conditions' => $conditions,
            'delay_seconds' => $validated['delay_seconds'] ?? 0,
            'cooldown_hours' => $validated['cooldown_hours'] ?? 24,
            'priority' => $validated['priority'] ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Auto-greeting updated');
    }

    public function destroy(AutoGreeting $autoGreeting)
    {
        $autoGreeting->delete();
        return back()->with('success', 'Auto-greeting deleted');
    }

    public function toggle(AutoGreeting $autoGreeting)
    {
        $autoGreeting->update(['is_active' => !$autoGreeting->is_active]);
        return response()->json(['success' => true, 'is_active' => $autoGreeting->is_active]);
    }
}
