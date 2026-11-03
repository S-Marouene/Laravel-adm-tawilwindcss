<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        $this->authorize('view roles');

        $roles = Role::with('permissions')->latest()->paginate(10);
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $this->authorize('create roles');

        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode(' ', $permission->name)[0] ?? 'general';
        });
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $this->authorize('create roles');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
            $permNames = Permission::whereIn('id', $validated['permissions'])->pluck('name')->toArray();
        }

        ActivityLogger::created("role {$role->name}", $role, [
            'name' => $role->name,
            'permissions' => $permNames ?? [],
        ]);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $this->authorize('edit roles');

        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode(' ', $permission->name)[0] ?? 'general';
        });
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('edit roles');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.$role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $oldName = $role->name;
        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        } else {
            $role->syncPermissions([]);
        }

        ActivityLogger::updated("role {$role->name}", $role, [
            'name' => ['old' => $oldName, 'new' => $role->name],
        ]);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $this->authorize('delete roles');

        if (in_array($role->name, ['admin', 'super-admin'])) {
            return back()->with('error', 'Cannot delete a built-in administration role.');
        }

        $roleName = $role->name;
        $role->delete();

        ActivityLogger::deleted("role {$roleName}", $role, ['name' => $roleName]);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
