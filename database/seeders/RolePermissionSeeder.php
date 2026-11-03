<?php

namespace Database\Seeders;

use App\Models\Delegation;
use App\Models\Governorate;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permission matrix grouped per back-office role.
     */
    private const ROLE_PERMISSIONS = [
        'super-admin' => '*', // everything — managed via Gate::before bypass

        // Back-office administration: users, roles, references, dossiers, logs
        'admin' => null, // = every permission except website settings (built below)

        // Central office: consultations & management of all dossiers
        'centrale' => [
            'view prevention-dossiers', 'manage prevention-dossiers',
            'view preventive-presence', 'manage preventive-presence',
            'view rescue-certificates', 'manage rescue-certificates',
            'view works-notifications', 'manage works-notifications',
        ],

        // Governed by one governorate (all its delegations) — scope applied by AdminScope.
        // The service filter (service_user pivot) decides which services he actually sees.
        'gouvernorat' => [
            'view prevention-dossiers', 'manage prevention-dossiers',
            'view preventive-presence', 'manage preventive-presence',
            'view rescue-certificates', 'manage rescue-certificates',
            'view works-notifications', 'manage works-notifications',
        ],

        // Bound to one delegation only — scope applied by AdminScope
        'delegation' => [
            'view prevention-dossiers', 'manage prevention-dossiers',
            'view preventive-presence', 'manage preventive-presence',
            'view rescue-certificates', 'manage rescue-certificates',
            'view works-notifications', 'manage works-notifications',
        ],

        // Manages delegation & governorate names, building types/activities,
        // and the required documents per service.
        'reference-manager' => [
            'view governorates', 'create governorates', 'edit governorates', 'delete governorates',
            'view delegations', 'create delegations', 'edit delegations', 'delete delegations',
            'view building-types', 'create building-types', 'edit building-types', 'delete building-types',
            'view building-activities', 'create building-activities', 'edit building-activities', 'delete building-activities',
            'view document-types', 'create document-types', 'edit document-types', 'delete document-types',
        ],
    ];

    /** Everything the "admin" role gets (i.e. all permissions except settings). */
    private const ADMIN_EXCLUDED_PERMISSIONS = [
        'view settings', 'edit settings',
        'view theme-settings', 'edit theme-settings',
    ];

    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ------------------------------------------------------------------
        // Permissions
        // ------------------------------------------------------------------
        $permissions = [
            // User permissions
            'view users',
            'create users',
            'edit users',
            'delete users',
            // Role permissions
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            // Permission management
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',
            // Governorates
            'view governorates',
            'create governorates',
            'edit governorates',
            'delete governorates',
            // Delegations
            'view delegations',
            'create delegations',
            'edit delegations',
            'delete delegations',
            // Building types
            'view building-types',
            'create building-types',
            'edit building-types',
            'delete building-types',
            // Building activities
            'view building-activities',
            'create building-activities',
            'edit building-activities',
            'delete building-activities',
            // Document types
            'view document-types',
            'create document-types',
            'edit document-types',
            'delete document-types',
            // Prevention dossiers
            'view prevention-dossiers',
            'manage prevention-dossiers',
            // Preventive presence requests
            'view preventive-presence',
            'manage preventive-presence',
            // Rescue & relief certificates (شهادة نجدة وإنقاذ)
            'view rescue-certificates',
            'manage rescue-certificates',
            // Works notifications (الإعلام عن إنجاز أشغال لا تستوجب رخصة بناء)
            'view works-notifications',
            'manage works-notifications',
            // Activity logs
            'view activity-logs',
            'delete activity-logs',
            // Website settings (super-admin only)
            'view settings',
            'edit settings',
            // Theme settings (super-admin only)
            'view theme-settings',
            'edit theme-settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ------------------------------------------------------------------
        // Roles — see the constants above for the permission matrix
        // ------------------------------------------------------------------
        foreach (self::ROLE_PERMISSIONS as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if ($roleName === 'super-admin') {
                $role->givePermissionTo(Permission::all());
            } elseif ($roleName === 'admin') {
                // All permissions except website & theme settings
                $role->givePermissionTo($this->adminPermissions());
            } elseif (in_array($roleName, ['centrale', 'gouvernorat', 'delegation'])) {
                // Additive so newly introduced services are granted on re-seed;
                // the actual service visibility comes from the service_user pivot.
                $role->givePermissionTo($rolePermissions);
            } elseif ($role->permissions->isEmpty()) {
                $role->givePermissionTo($rolePermissions);
            }
        }

        // Reference data: the services registry backing the user-service assignment
        $this->call(ServiceSeeder::class);

        // ------------------------------------------------------------------
        // Administrator accounts
        // ------------------------------------------------------------------

        // Legacy main admin (full back-office account)
        $admin = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name'              => 'Admin',
                'password'          => bcrypt('password'),
                'is_admin'          => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Super-admin: owns website settings, theme and user management
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@onpc.tn'],
            [
                'name'              => 'Super Administrateur',
                'password'          => bcrypt('password'),
                'is_admin'          => true,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('super-admin');

        // Centrale: sees every dossier from every delegation
        User::updateOrCreate(
            ['email' => 'centrale@onpc.tn'],
            [
                'name'              => 'Direction Centrale',
                'password'          => bcrypt('password'),
                'is_admin'          => false, // access comes from the role
                'email_verified_at' => now(),
            ]
        )->syncRoles(['centrale']);

        // Centrale sees every service by default; trim that via the admin panel.
        $centrale = User::where('email', 'centrale@onpc.tn')->first();
        $centrale?->services()->sync(Service::query()->pluck('id')->all());

        // Gouvernorat: bound to one governorate (all its delegations)
        User::updateOrCreate(
            ['email' => 'gouvernorat@onpc.tn'],
            [
                'name'              => 'Administrateur Gouvernorat',
                'password'          => bcrypt('password'),
                'is_admin'          => false, // access comes from the role
                'governorate_id'    => Governorate::query()->orderBy('id')->value('id'),
                'email_verified_at' => now(),
            ]
        )->syncRoles(['gouvernorat']);

        $preventionService = Service::where('key', Service::SERVICE_PREVENTION)->value('id');

        // Gouvernorat demo user: handles the prevention service only for now.
        User::where('email', 'gouvernorat@onpc.tn')->first()
            ?->services()->sync([$preventionService]);

        // Delegation: bound to a single delegation
        $firstDelegation = Delegation::query()->orderBy('governorate_id')->orderBy('id')->first();
        User::updateOrCreate(
            ['email' => 'delegation@onpc.tn'],
            [
                'name'              => 'Administrateur Délégation',
                'password'          => bcrypt('password'),
                'is_admin'          => false, // access comes from the role
                'governorate_id'    => $firstDelegation?->governorate_id,
                'delegation_id'     => $firstDelegation?->id,
                'email_verified_at' => now(),
            ]
        )->syncRoles(['delegation']);

        // Delegation demo user: handles the prevention service only for now.
        User::where('email', 'delegation@onpc.tn')->first()
            ?->services()->sync([$preventionService]);

        // Reference data manager: delegations names, required documents…
        User::updateOrCreate(
            ['email' => 'reference@onpc.tn'],
            [
                'name'              => 'Gestionnaire Référentiel',
                'password'          => bcrypt('password'),
                'is_admin'          => false, // access comes from the role
                'email_verified_at' => now(),
            ]
        )->syncRoles(['reference-manager']);
    }

    private function adminPermissions(): array
    {
        return Permission::query()
            ->whereNotIn('name', self::ADMIN_EXCLUDED_PERMISSIONS)
            ->pluck('name')
            ->all();
    }
}
