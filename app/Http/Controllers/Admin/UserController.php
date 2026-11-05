<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delegation;
use App\Models\Governorate;
use App\Models\Service;
use App\Models\User;
use App\Models\UserTerritory;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('view users');

        $users = User::with(['roles', 'territories.governorate', 'territories.delegation', 'services'])
            ->latest()
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('create users');

        $roles       = $this->availableRoles();
        $governorates = Governorate::with('delegations')->orderBy('id')->get();
        $services    = Service::orderBy('sort_order')->get();

        return view('admin.users.create', compact('roles', 'governorates', 'services'));
    }

    public function store(Request $request)
    {
        $this->authorize('create users');

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['nullable', 'string', 'exists:roles,name'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
            // Territories: array of objects sent as territories[0][governorate_id], territories[0][delegation_id]
            'territories'                  => ['nullable', 'array'],
            'territories.*.governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'territories.*.delegation_id'  => ['nullable', 'integer', 'exists:delegations,id'],
        ]);

        $this->validateRoleExtras($request, $validated);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $this->syncRoleSafely($user, $validated['role'] ?? null);
        $user->services()->sync($validated['services'] ?? []);
        $this->syncTerritories($user, $validated['territories'] ?? []);

        ActivityLogger::created("user {$user->name} ({$user->email})", $user, [
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $validated['role'] ?? null,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $this->authorize('edit users');

        $roles        = $this->availableRoles();
        $currentRole  = $user->roles->first()?->name;
        $governorates = Governorate::with('delegations')->orderBy('id')->get();
        $services     = Service::orderBy('sort_order')->get();
        $user->load(['territories.governorate', 'territories.delegation', 'services']);

        return view('admin.users.edit', compact(
            'user', 'roles', 'currentRole', 'governorates', 'services',
        ));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('edit users');

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role'     => ['nullable', 'string', 'exists:roles,name'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
            'territories'                  => ['nullable', 'array'],
            'territories.*.governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'territories.*.delegation_id'  => ['nullable', 'integer', 'exists:delegations,id'],
        ]);

        $this->validateRoleExtras($request, $validated);

        $changes = [];
        if ($user->name !== $validated['name'])  $changes['name']  = ['old' => $user->name,  'new' => $validated['name']];
        if ($user->email !== $validated['email']) $changes['email'] = ['old' => $user->email, 'new' => $validated['email']];

        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $this->syncRoleSafely($user, $validated['role'] ?? null);
        $user->services()->sync($validated['services'] ?? []);
        $this->syncTerritories($user, $validated['territories'] ?? []);

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

        $userName  = $user->name;
        $userEmail = $user->email;
        $user->delete();

        ActivityLogger::deleted("user {$userName} ({$userEmail})", $user, [
            'name'  => $userName,
            'email' => $userEmail,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function availableRoles()
    {
        $query = Role::orderBy('name');

        if (! auth()->user()->hasRole('super-admin')) {
            $query->where('name', '!=', 'super-admin');
        }

        return $query->get();
    }

    /**
     * Additional validation rules that depend on the chosen role:
     * - manager  → at least one territory, at least one service
     * - centrale → at least one service
     * - Each territory row: if delegation_id is set, it must belong to governorate_id
     * - super-admin may only be granted by a super-admin
     */
    private function validateRoleExtras(Request $request, array $validated): void
    {
        $role = $validated['role'] ?? null;

        if ($role === 'manager') {
            $request->validate([
                'territories'   => ['required', 'array', 'min:1'],
                'services'      => ['required', 'array', 'min:1'],
            ], [
                'territories.required' => 'Assign at least one territory for a manager.',
                'territories.min'      => 'Assign at least one territory for a manager.',
                'services.required'    => 'Select at least one service for a manager.',
            ]);
        }

        if ($role === 'centrale') {
            $request->validate([
                'services' => ['required', 'array', 'min:1'],
            ], [
                'services.required' => 'Select at least one service for a centrale user.',
            ]);
        }

        // Validate each territory: delegation must belong to its governorate
        foreach ($validated['territories'] ?? [] as $i => $territory) {
            $govId = $territory['governorate_id'] ?? null;
            $delId = $territory['delegation_id'] ?? null;

            if ($delId) {
                $delegation = Delegation::find($delId);
                if ($delegation && (int) $delegation->governorate_id !== (int) $govId) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "territories.{$i}.delegation_id" => "Delegation does not belong to the selected governorate.",
                    ]);
                }
            }
        }

        if ($role === 'super-admin' && ! auth()->user()->hasRole('super-admin')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'role' => 'Only a super-admin may grant the super-admin role.',
            ]);
        }
    }

    /**
     * Sync the user's territory assignments.
     * Deletes all existing rows then re-inserts the submitted ones.
     */
    private function syncTerritories(User $user, array $territories): void
    {
        $user->territories()->delete();

        foreach ($territories as $territory) {
            $govId = $territory['governorate_id'] ?? null;
            $delId = $territory['delegation_id'] ?? null;

            if (! $govId) {
                continue;
            }

            UserTerritory::firstOrCreate([
                'user_id'        => $user->id,
                'governorate_id' => $govId,
                'delegation_id'  => $delId ?: null,
            ]);
        }
    }

    /**
     * Assign a single role safely.
     * Non-super-admins cannot touch existing super-admin accounts.
     */
    private function syncRoleSafely(User $user, ?string $roleName): void
    {
        if ($user->hasRole('super-admin') && ! auth()->user()->hasRole('super-admin')) {
            return;
        }

        if ($roleName === 'super-admin' && ! auth()->user()->hasRole('super-admin')) {
            return;
        }

        $user->syncRoles($roleName ? [$roleName] : []);
    }
}
