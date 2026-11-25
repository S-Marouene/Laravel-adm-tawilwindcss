<?php

use App\Models\TrainingPlannedSession;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Makes the training permissions available on an existing installation without
 * requiring a full re-seed. RolePermissionSeeder remains the source of truth
 * (it syncs the full matrix when `db:seed` is run).
 */
return new class extends Migration
{
    private const TRAINING_PERMISSIONS = [
        'view training', 'manage training',
    ];

    private const ROLE_PERMISSIONS = [
        'super-admin' => null, // handled by the seeder (all permissions)
        'admin'       => null, // handled by the seeder (all except settings)
        'centrale'    => [
            'view training', 'manage training',
            TrainingPlannedSession::PERM_VIEW, TrainingPlannedSession::PERM_CREATE,
            TrainingPlannedSession::PERM_EDIT, TrainingPlannedSession::PERM_DELETE,
        ],
        'manager' => [
            'view training', TrainingPlannedSession::PERM_VIEW,
        ],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = array_merge(self::TRAINING_PERMISSIONS, TrainingPlannedSession::PERMISSIONS);

        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            if ($permissions === null) {
                continue;
            }

            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = array_merge(self::TRAINING_PERMISSIONS, TrainingPlannedSession::PERMISSIONS);

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            if ($permissions === null) {
                continue;
            }

            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->revokePermissionTo($permissions);
        }

        Permission::whereIn('name', $names)->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
