<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('dashboard.settings.profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'pseudo_names' => 'nullable|array',
            'pseudo_names.*' => 'nullable|string|max:100',
            'active_pseudo_name' => 'nullable|string|max:100',
        ]);

        // Filter out empty pseudo names
        $pseudoNames = array_filter($validated['pseudo_names'] ?? [], fn($name) => !empty(trim($name)));

        $user->update([
            'name' => $validated['name'],
            'pseudo_names' => array_values($pseudoNames),
            'active_pseudo_name' => $validated['active_pseudo_name'] ?: (count($pseudoNames) > 0 ? $pseudoNames[0] : null),
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }
}
