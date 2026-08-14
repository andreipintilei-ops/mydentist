<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
        // Reference data. Idempotent, so this is safe to re-run on deploy.
        $this->call([
            CountySeeder::class,
            CitySeeder::class,
            ServiceSeeder::class,
        ]);

        // Fictional demo clinics — NEVER in production (they'd be indexed as real
        // practices). The Clinic global scope hides them and clinics:purge-demo
        // removes them, but the surest guard is not seeding them there at all.
        if (! app()->environment('production')) {
            $this->call(DemoClinicSeeder::class);
        }

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Local convenience: an admin to reach the dashboard/owner tools.
        if (! app()->environment('production')) {
            User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => UserRole::Admin,
            ]);
        }
    }
}
