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
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name'     => 'Test User',
                'password' => bcrypt('password'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'medaziznefzi1@gmail.com'],
            [
                'name'     => 'Med Aziz Nefzi',
                'password' => bcrypt('ONPC198++'),
            ],
        );

        $this->call([
            RolePermissionSeeder::class,
            GovernorateSeeder::class,
            BuildingTypeSeeder::class,
            DocumentTypeSeeder::class,
            PreventivePresenceDocumentTypeSeeder::class,
            RescueDocumentTypeSeeder::class,
            WorksDocumentTypeSeeder::class,
        ]);
    }
}
