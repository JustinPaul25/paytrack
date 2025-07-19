<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class RolesController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::with('permissions');
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        $roles = $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();
        $roles->getCollection()->transform(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions_count' => $role->permissions->count(),
                'updated_at' => $role->updated_at,
            ];
        });
        if ($request->ajax() && !$request->hasHeader('X-Inertia')) {
            return response()->json([
                'roles' => $roles,
            ]);
        }
        return Inertia::render('roles/Index', [
            'roles' => $roles,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        $permissions = Permission::all();
        $grouped = $permissions->groupBy(function ($permission) {
            // Group by model/resource, assuming permission names are like 'view user', 'edit user', etc.
            $parts = explode(' ', $permission->name);
            return Str::title(end($parts));
        });

        return Inertia::render('roles/Create', [
            'groupedPermissions' => $grouped,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'guard_name' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'],
        ]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        // If the request has a flag for 'create another', redirect back to create
        if ($request->has('create_another')) {
            return redirect()->route('roles.create')->with('success', 'Role created successfully!');
        }

        return redirect()->route('roles.index')->with('success', 'Role created successfully!');
    }

    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::all();
        $grouped = $permissions->groupBy(function ($permission) {
            $parts = explode(' ', $permission->name);
            return Str::title(end($parts));
        });
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        return Inertia::render('roles/Edit', [
            'role' => $role,
            'groupedPermissions' => $grouped,
            'rolePermissions' => $rolePermissions,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'guard_name' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);
        $role->update([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'],
        ]);
        $role->syncPermissions($validated['permissions'] ?? []);
        return redirect()->route('roles.index')->with('success', 'Role updated successfully!');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        // return redirect()->route('roles.index')->with('success', 'Role deleted successfully!');
    }
} 