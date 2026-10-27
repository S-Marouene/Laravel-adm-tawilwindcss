<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create admin role and assign all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        // Create or update the admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');
    }
}
