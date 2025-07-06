<?php

namespace Database\Seeders;

use App\Models\Auth\User;
use App\Models\Auth\Track;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist before assigning them
        // You should have a RolePermissionSeeder that creates these roles
        
        // Create admin user
        User::factory()->staff()->create([
            'portal_user_id' => 'ADMIN001',
            'email' => 'admin@itivent.com',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'branch' => 'Cairo',
        ])->assignRole('admin');

        // Create a specific staff user
        User::factory()->staff()->create([
            'portal_user_id' => 'STAFF001',
            'email' => 'staff@itivent.com',
            'first_name' => 'Event',
            'last_name' => 'Coordinator',
            'branch' => 'Cairo',
        ])->assignRole('staff');

        // Create general staff members (branch is assigned automatically by the factory)
        User::factory(5)->staff()->create()->each(function ($user) {
            $user->assignRole('staff');
        });

        // Create students for each track
        $tracks = Track::all();
        if ($tracks->isEmpty()) {
            // Create a fallback track if none exist
            $tracks = Track::factory(1)->create();
        }

        foreach ($tracks as $track) {
            User::factory(15)->student()->create([
                'track_id' => $track->id,
            ])->each(function ($user) {
                $user->assignRole('student');
            });
        }

        // Create some alumni (who are also students in terms of roles for this system)
        User::factory(20)->student()->create([
            'track_id' => $tracks->random()->id,
            'intake_year' => fake()->numberBetween(2018, 2022),
            'graduation_year' => fake()->numberBetween(2022, 2024),
        ])->each(function ($user) {
            $user->assignRole('student');
        });
    }
}
