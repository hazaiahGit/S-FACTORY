<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;

        $users = User::with(['branch', 'roles'])
            ->where('business_id', $businessId)
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(Request $request)
    {
        $businessId = $request->user()->business_id;

        $branches = Branch::where('business_id', $businessId)->get(['id', 'name']);
        $roles = Role::all(['id', 'name']);
        $permissions = Permission::all(['id', 'name']);

        return Inertia::render('Users/Create', [
            'branches' => $branches,
            'roles' => $roles,
            'permissions' => ['System Permissions' => $permissions],
        ]);
    }

    public function store(Request $request)
    {
        $businessId = $request->user()->business_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'branch_id' => 'required|exists:branches,id',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|exists:roles,name',
            'permissions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $businessId) {
            $user = User::create([
                'business_id' => $businessId,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'branch_id' => $validated['branch_id'],
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $user->assignRole($validated['role']);

            if (! empty($validated['permissions'])) {
                $user->givePermissionTo($validated['permissions']);
            }
        });

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(Request $request, User $user)
    {
        $businessId = $request->user()->business_id;

        // Ensure user belongs to this business
        if ($user->business_id !== $businessId && $user->id !== 1) {
            abort(403);
        }

        $branches = Branch::where('business_id', $businessId)->get(['id', 'name']);
        $roles = Role::all(['id', 'name']);
        $permissions = Permission::all(['id', 'name']);

        $user->load('roles', 'permissions');

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'branches' => $branches,
            'roles' => $roles,
            'permissions' => ['System Permissions' => $permissions],
        ]);
    }

    public function update(Request $request, User $user)
    {
        $businessId = $request->user()->business_id;

        if ($user->business_id !== $businessId && $user->id !== 1) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'branch_id' => 'required|exists:branches,id',
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|exists:roles,name',
            'permissions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $user) {
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->phone = $validated['phone'] ?? null;
            $user->branch_id = $validated['branch_id'];
            $user->is_active = $validated['is_active'] ?? true;

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            // Sync roles and permissions
            $user->syncRoles([$validated['role']]);

            if (isset($validated['permissions'])) {
                $user->syncPermissions($validated['permissions']);
            }
        });

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }
}
