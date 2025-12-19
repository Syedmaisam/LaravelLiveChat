<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'manager', 'clients'])->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $managers = User::whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'manager']))->get();
        $clients = Client::where('is_active', true)->get();
        return view('admin.users.form', compact('roles', 'managers', 'clients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|exists:roles,id',
            'manager_id' => 'nullable|exists:users,id',
            'clients' => 'nullable|array',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'manager_id' => $validated['manager_id'],
        ]);

        $user->roles()->attach($validated['role']);
        
        if (!empty($validated['clients'])) {
            $user->clients()->sync($validated['clients']);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $managers = User::whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'manager']))->where('id', '!=', $user->id)->get();
        $clients = Client::where('is_active', true)->get();
        return view('admin.users.form', compact('user', 'roles', 'managers', 'clients'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
            'role' => 'required|exists:roles,id',
            'manager_id' => 'nullable|exists:users,id',
            'clients' => 'nullable|array',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'manager_id' => $validated['manager_id'],
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->roles()->sync([$validated['role']]);
        $user->clients()->sync($validated['clients'] ?? []);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete yourself.');
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
