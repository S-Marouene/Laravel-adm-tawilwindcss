<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters: governorates & delegations must exist before
     * RolePermissionSeeder (which links admin users to a territory), and
     * building types must exist before DocumentTypeSeeder (which links
     * document types to building types).
     */
    public function run(): void
    {
        // Citizen / demo accounts (no territory needed)
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'medaziznefzi1@gmail.com'],
            [
                'name' => 'Med Aziz Nefzi',
                'password' => bcrypt('ONPC198++'),
            ],
        );

        $this->call([
            GovernorateSeeder::class,        // governorates + delegations (no deps)
            BuildingTypeSeeder::class,       // building types + activities (no deps)
            RolePermissionSeeder::class,     // roles, permissions, services, admin users
            // (needs governorates/delegations for territory)
            DocumentTypeSeeder::class,       // prevention document types (needs building types)
            PreventivePresenceDocumentTypeSeeder::class,
            RescueDocumentTypeSeeder::class,
            BuildingWorksDocumentTypeSeeder::class,
            SafetyFileDocumentTypeSeeder::class, // safety file document types — twin services (needs building types)
            NotificationTemplateSeeder::class, // notification templates (used by DossierNotification)
            TranslationSeeder::class,          // dictionary FR/AR/EN from lang/ files (editable by admins)
        ]);
    }
}
