<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delegation;
use App\Models\Governorate;
use App\Models\Service;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('view users');

        $users = User::with(['roles', 'governorate', 'delegation', 'services'])->latest()->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('create users');

        $roles = Role::all();
        $governorates = Governorate::with('delegations')->orderBy('id')->get();
        $services = Service::orderBy('sort_order')->get();

        return view('admin.users.create', compact('roles', 'governorates', 'services'));
    }

    public function store(Request $request)
    {
        $this->authorize('create users');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'is_admin' => ['boolean'],
            'governorate_id' => ['nullable', 'integer', 'exists:governorates,id'],
            'delegation_id' => ['nullable', 'integer', 'exists:delegations,id'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
        ]);

        $this->validateTerritory($request, $validated);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
            'governorate_id' => $validated['governorate_id'] ?? null,
            'delegation_id' => $validated['delegation_id'] ?? null,
        ]);

        $this->syncRolesSafely($user, $validated['roles'] ?? []);

        // Services this administrator handles (multi-select)
        $user->services()->sync($validated['services'] ?? []);

        ActivityLogger::created("user {$user->name} ({$user->email})", $user, [
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => $user->is_admin,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $this->authorize('edit users');

        $roles = Role::all();
        $userRoles = $user->roles->pluck('id')->toArray();
        $governorates = Governorate::with('delegations')->orderBy('id')->get();
        $services = Service::orderBy('sort_order')->get();
        $user->load(['governorate', 'delegation']);

        return view('admin.users.edit', compact(
            'user', 'roles', 'userRoles', 'governorates', 'services',
        ));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('edit users');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'is_admin' => ['boolean'],
            'governorate_id' => ['nullable', 'integer', 'exists:governorates,id'],
            'delegation_id' => ['nullable', 'integer', 'exists:delegations,id'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
        ]);

        $this->validateTerritory($request, $validated);

        $changes = [];
        if ($user->name !== $validated['name']) $changes['name'] = ['old' => $user->name, 'new' => $validated['name']];
        if ($user->email !== $validated['email']) $changes['email'] = ['old' => $user->email, 'new' => $validated['email']];

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => $validated['is_admin'] ?? $user->is_admin,
            'governorate_id' => $validated['governorate_id'] ?? null,
            'delegation_id' => $validated['delegation_id'] ?? null,
        ]);

        if (isset($validated['password']) && $validated['password']) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $this->syncRolesSafely($user, $validated['roles'] ?? []);

        // Services this administrator handles (multi-select)
        $user->services()->sync($validated['services'] ?? []);

        ActivityLogger::updated("user {$user->name}", $user, $changes);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete users');

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }

        if ($user->hasRole('super-admin') && ! auth()->user()->hasRole('super-admin')) {
            return back()->with('error', 'Only a super-admin can delete a super-admin account.');
        }

        $userName = $user->name;
        $userEmail = $user->email;
        $user->delete();

        ActivityLogger::deleted("user {$userName} ({$userEmail})", $user, [
            'name' => $userName,
            'email' => $userEmail,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    // =====================================================================
    // Territory validation — delegated to the roles picked on the form
    // =====================================================================

    /**
     * - A 'delegation' administrator must be bound to a delegation that
     *   belongs to the selected governorate.
     * - A 'gouvernorat' administrator must be bound to a governorate.
     * - Only a super-admin may grant the 'super-admin' role.
     */
    private function validateTerritory(Request $request, array $validated): void
    {
        $roleNames = Role::whereIn('id', $validated['roles'] ?? [])->pluck('name');

        $isDelegation = $roleNames->contains('delegation');
        $isGovernorate = $roleNames->contains('gouvernorat');
        $isCentrale = $roleNames->contains('centrale');

        if ($isDelegation || $isGovernorate) {
            $request->validate([
                'governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            ], [
                'governorate_id.required' => 'A governorate is required for a gouvernorat or delegation administrator.',
            ]);
        }

        // Centrale / gouvernorat / delegation administrators must handle at
        // least one service — the service list decides which dossier menu they get.
        if ($isDelegation || $isGovernorate || $isCentrale) {
            $request->validate([
                'services' => ['required', 'array', 'min:1'],
            ], [
                'services.required' => 'Select at least one service for this administrator.',
            ]);
        }

        if ($isDelegation) {
            $request->validate([
                'delegation_id' => ['required', 'integer', 'exists:delegations,id'],
            ], [
                'delegation_id.required' => 'A delegation is required for a delegation administrator.',
            ]);

            $delegation = Delegation::find($validated['delegation_id']);
            if ($delegation && (int) $delegation->governorate_id !== (int) $validated['governorate_id']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'delegation_id' => 'The selected delegation does not belong to the selected governorate.',
                ]);
            }
        }

        if ($roleNames->contains('super-admin') && ! auth()->user()->hasRole('super-admin')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'roles' => 'Only a super-admin may grant the super-admin role.',
            ]);
        }
    }

    /**
     * Sync roles, but never let a non-super-admin touch super-admin accounts.
     */
    private function syncRolesSafely(User $user, array $roleIds): void
    {
        $roleNames = Role::whereIn('id', $roleIds)->pluck('name');

        if ($user->hasRole('super-admin') && ! auth()->user()->hasRole('super-admin')) {
            $roleNames = $roleNames->push('super-admin')->unique();
        } elseif ($roleNames->contains('super-admin') && ! auth()->user()->hasRole('super-admin')) {
            $roleNames = $roleNames->reject(fn ($name) => $name === 'super-admin');
        }

        $user->syncRoles($roleNames->all());
    }
}
