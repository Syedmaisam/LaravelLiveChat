<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CannedResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CannedResponseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $responses = CannedResponse::where('user_id', $user->id)
            ->orWhere('is_global', true)
            ->orderBy('category')
            ->orderBy('title')
            ->get()
            ->groupBy('category');

        return view('dashboard.canned-responses.index', compact('responses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shortcut' => 'required|string|max:50|regex:/^\/[a-z0-9_]+$/',
            'title' => 'required|string|max:100',
            'content' => 'required|string|max:2000',
            'category' => 'nullable|string|max:50',
        ]);

        $user = Auth::user();

        // Check for duplicate shortcut
        $exists = CannedResponse::where('user_id', $user->id)
            ->where('shortcut', $validated['shortcut'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'This shortcut already exists');
        }

        CannedResponse::create([
            'user_id' => $user->id,
            'shortcut' => $validated['shortcut'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'category' => $validated['category'] ?: 'General',
        ]);

        return back()->with('success', 'Canned response created');
    }

    public function update(Request $request, CannedResponse $cannedResponse)
    {
        $user = Auth::user();

        // Only owner can edit (or admin for global)
        if ($cannedResponse->user_id !== $user->id && !$cannedResponse->is_global) {
            abort(403);
        }

        $validated = $request->validate([
            'shortcut' => 'required|string|max:50|regex:/^\/[a-z0-9_]+$/',
            'title' => 'required|string|max:100',
            'content' => 'required|string|max:2000',
            'category' => 'nullable|string|max:50',
        ]);

        $cannedResponse->update($validated);

        return back()->with('success', 'Canned response updated');
    }

    public function destroy(CannedResponse $cannedResponse)
    {
        $user = Auth::user();

        if ($cannedResponse->user_id !== $user->id) {
            abort(403);
        }

        $cannedResponse->delete();

        return back()->with('success', 'Canned response deleted');
    }

    /**
     * API endpoint to get canned responses for chat input
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        $query = $request->get('q', '');
        $clientId = $request->get('client_id');

        $responses = CannedResponse::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('is_global', true);
        })
        ->when($query, function ($q, $query) {
            $q->where(function ($q2) use ($query) {
                $q2->where('shortcut', 'like', "%{$query}%")
                   ->orWhere('title', 'like', "%{$query}%")
                   ->orWhere('content', 'like', "%{$query}%");
            });
        })
        ->when($clientId, function ($q, $clientId) {
            $q->where(function ($q2) use ($clientId) {
                $q2->whereNull('client_id')
                   ->orWhere('client_id', $clientId);
            });
        })
        ->orderBy('usage_count', 'desc')
        ->limit(10)
        ->get(['id', 'shortcut', 'title', 'content', 'category']);

        return response()->json($responses);
    }

    /**
     * Track usage when a response is used
     */
    public function use(CannedResponse $cannedResponse)
    {
        $cannedResponse->incrementUsage();
        return response()->json(['success' => true]);
    }
}
