<?php

namespace Database\Seeders;

use App\Models\Delegation;
use App\Models\Governorate;
use App\Models\Service;
use App\Models\User;
use App\Models\UserTerritory;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permission matrix grouped per back-office role.
     *
     * Role hierarchy:
     *  super-admin  – everything including website / theme settings
     *  admin        – everything except website / theme settings; manages users
     *  centrale     – views & manages all dossiers across all territories (service-filtered)
     *  manager      – scoped to one or more territories; each territory is either a
     *                 whole governorate or a single delegation (service-filtered)
     *  user         – simple public citizen, no back-office access
     */
    private const ROLE_PERMISSIONS = [
        'super-admin' => '*',

        'admin' => null,

        'centrale' => [
            'view prevention-dossiers', 'manage prevention-dossiers',
            'view preventive-presence', 'manage preventive-presence',
            'view rescue-certificates', 'manage rescue-certificates',
            'view building-works-declarations', 'manage building-works-declarations',
        ],

        'manager' => [
            'view prevention-dossiers', 'manage prevention-dossiers',
            'view preventive-presence', 'manage preventive-presence',
            'view rescue-certificates', 'manage rescue-certificates',
            'view building-works-declarations', 'manage building-works-declarations',
        ],

        'user' => [],
    ];

    /** Permissions excluded from the "admin" role (super-admin-only features). */
    private const ADMIN_EXCLUDED_PERMISSIONS = [
        'view settings',
        'edit settings',
        'view theme-settings',
        'edit theme-settings',
    ];

    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ------------------------------------------------------------------
        // All permissions
        // ------------------------------------------------------------------
        $permissions = [
            // Users
            'view users', 'create users', 'edit users', 'delete users',
            // Roles
            'view roles', 'create roles', 'edit roles', 'delete roles',
            // Permissions
            'view permissions', 'create permissions', 'edit permissions', 'delete permissions',
            // Governorates
            'view governorates', 'create governorates', 'edit governorates', 'delete governorates',
            // Delegations
            'view delegations', 'create delegations', 'edit delegations', 'delete delegations',
            // Building types
            'view building-types', 'create building-types', 'edit building-types', 'delete building-types',
            // Building activities
            'view building-activities', 'create building-activities', 'edit building-activities', 'delete building-activities',
            // Document types
            'view document-types', 'create document-types', 'edit document-types', 'delete document-types',
            // Prevention dossiers
            'view prevention-dossiers', 'manage prevention-dossiers',
            // Preventive presence
            'view preventive-presence', 'manage preventive-presence',
            // Rescue certificates
            'view rescue-certificates', 'manage rescue-certificates',
            // Works notifications
            'view building-works-declarations', 'manage building-works-declarations',
            // Activity logs
            'view activity-logs', 'delete activity-logs',
            // Website settings (super-admin only)
            'view settings', 'edit settings',
            // Theme settings (super-admin only)
            'view theme-settings', 'edit theme-settings',
            // Notification center (super-admin + admin)
            'manage notifications',
            // Translations dictionary (super-admin + admin)
            'manage translations',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ------------------------------------------------------------------
        // Roles & their permission assignments
        // ------------------------------------------------------------------
        foreach (self::ROLE_PERMISSIONS as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if ($roleName === 'super-admin') {
                $role->syncPermissions(Permission::all());
            } elseif ($roleName === 'admin') {
                $role->syncPermissions($this->adminPermissions());
            } elseif ($rolePermissions === []) {
                $role->syncPermissions([]);
            } else {
                $role->syncPermissions($rolePermissions);
            }
        }

        // ------------------------------------------------------------------
        // Services registry
        // ------------------------------------------------------------------
        $this->call(ServiceSeeder::class);

        // ------------------------------------------------------------------
        // Demo administrator accounts
        // ------------------------------------------------------------------

        // Super-admin: owns website settings, theme, full user management
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@onpc.tn'],
            [
                'name' => 'Super Administrateur',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->syncRoles(['super-admin']);

        // Admin: same as super-admin minus settings pages
        $admin = User::updateOrCreate(
            ['email' => 'admin@onpc.tn'],
            [
                'name' => 'Administrateur',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['admin']);

        // Centrale: sees every dossier from every delegation, all services
        $centrale = User::updateOrCreate(
            ['email' => 'centrale@onpc.tn'],
            [
                'name' => 'Direction Centrale',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $centrale->syncRoles(['centrale']);
        $centrale->services()->sync(Service::query()->pluck('id')->all());

        // Manager: scoped to first governorate (whole) + first delegation of second governorate
        $manager = User::updateOrCreate(
            ['email' => 'manager@onpc.tn'],
            [
                'name' => 'Responsable Régional',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $manager->syncRoles(['manager']);
        $preventionService = Service::where('key', Service::SERVICE_PREVENTION)->value('id');
        $manager->services()->sync([$preventionService]);

        // Assign territories: first governorate (all delegations) + first delegation of second gov
        $firstGov = Governorate::orderBy('id')->first();
        $secondGov = Governorate::orderBy('id')->skip(1)->first();
        $firstDelOfSecondGov = $secondGov
            ? Delegation::where('governorate_id', $secondGov->id)->orderBy('id')->first()
            : null;

        $manager->territories()->delete();
        if ($firstGov) {
            UserTerritory::firstOrCreate([
                'user_id' => $manager->id,
                'governorate_id' => $firstGov->id,
                'delegation_id' => null,  // whole governorate
            ]);
        }
        if ($firstDelOfSecondGov) {
            UserTerritory::firstOrCreate([
                'user_id' => $manager->id,
                'governorate_id' => $secondGov->id,
                'delegation_id' => $firstDelOfSecondGov->id, // single delegation
            ]);
        }

        // Simple public user
        $user = User::updateOrCreate(
            ['email' => 'user@onpc.tn'],
            [
                'name' => 'Citoyen Test',
                'password' => bcrypt('password'),

                'email_verified_at' => now(),
            ]
        );
        $user->syncRoles(['user']);
    }

    private function adminPermissions(): array
    {
        return Permission::query()
            ->whereNotIn('name', self::ADMIN_EXCLUDED_PERMISSIONS)
            ->pluck('name')
            ->all();
    }
}
