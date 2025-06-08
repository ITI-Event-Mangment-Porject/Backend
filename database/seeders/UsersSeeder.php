<?php

namespace Database\Seeders;

use App\Models\Auth\User;
use App\Models\Auth\Track;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin users
        $adminTrack = Track::first();
        
        User::factory()->create([
            'portal_user_id' => 'ADMIN001',
            'email' => 'admin@itivent.com',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'track_id' => null,
            'intake_year' => null,
            'graduation_year' => null,
        ]);

        User::factory()->create([
            'portal_user_id' => 'STAFF001',
            'email' => 'staff@itivent.com',
            'first_name' => 'Event',
            'last_name' => 'Coordinator',
            'track_id' => null,
            'intake_year' => null,
            'graduation_year' => null,
        ]);

        // Create students for each track
        $tracks = Track::all();
        foreach ($tracks as $track) {
            User::factory(15)->create([
                'track_id' => $track->id,
                'intake_year' => fake()->numberBetween(2020, 2024),
                'graduation_year' => fake()->numberBetween(2024, 2026),
            ]);
        }

        // Create some alumni
        User::factory(20)->create([
            'track_id' => $tracks->random()->id,
            'intake_year' => fake()->numberBetween(2018, 2022),
            'graduation_year' => fake()->numberBetween(2022, 2024),
        ]);

        // Create staff members
        User::factory(5)->staff()->create();
    }
}
