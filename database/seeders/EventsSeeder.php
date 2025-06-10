<?php

namespace Database\Seeders;

use App\Models\Event\Event;
use App\Models\Event\EventSession;
use App\Models\Registration_and_interview\EventRegistration;
use App\Models\Event\EventStaffAssignment;
use App\Models\Auth\User;
use App\Models\Auth\Track;
use Illuminate\Database\Seeder;

class EventsSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::first();
        $tracks = Track::all();

        // Create upcoming job fair
        $jobFair = Event::factory()->create([
            'title' => 'Annual Tech Job Fair 2025',
            'slug' => 'annual-tech-job-fair-2025',
            'type' => 'Job Fair',
            'status' => 'published',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(31)->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'location' => 'ITI Main Campus',
            'registration_deadline' => now()->addDays(25),
            'created_by' => $creator->id,
        ]);

        // Create past job fair
        $pastJobFair = Event::factory()->create([
            'title' => 'Spring Job Fair 2024',
            'slug' => 'spring-job-fair-2024',
            'type' => 'Job Fair',
            'status' => 'completed',
            'start_date' => now()->subDays(60)->format('Y-m-d'),
            'end_date' => now()->subDays(59)->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'location' => 'ITI Main Campus',
            'created_by' => $creator->id,
        ]);

        // Create tech events
        $techEvent = Event::factory()->create([
            'title' => 'AI & Machine Learning Workshop',
            'slug' => 'ai-ml-workshop-2025',
            'type' => 'Tech',
            'status' => 'published',
            'start_date' => now()->addDays(15)->format('Y-m-d'),
            'end_date' => now()->addDays(15)->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '16:00:00',
            'location' => 'ITI Lab A',
            'created_by' => $creator->id,
        ]);

        // Create fun event
        $funEvent = Event::factory()->create([
            'title' => 'ITI Game Night',
            'slug' => 'iti-game-night-2025',
            'type' => 'Fun',
            'status' => 'published',
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '18:00:00',
            'end_time' => '22:00:00',
            'location' => 'ITI Recreation Center',
            'created_by' => $creator->id,
        ]);

        // Create additional events
        Event::factory(10)->create(['created_by' => $creator->id]);

        // Create event sessions for tech event
        EventSession::factory(5)->create(['event_id' => $techEvent->id]);
        EventSession::factory()->breakSession()->create([
            'event_id' => $techEvent->id,
            'title' => 'Coffee Break',
            'start_time' => '12:00:00',
            'end_time' => '12:30:00',
        ]);

        // Create event registrations
        $students = User::whereNotNull('track_id')->take(50)->get();
        foreach ([$jobFair, $pastJobFair, $techEvent, $funEvent] as $event) {
            foreach ($students->random(30) as $student) {
                EventRegistration::factory()->create([
                    'event_id' => $event->id,
                    'user_id' => $student->id,
                    'status' => $event->status === 'completed' ? 
                        fake()->randomElement(['attended', 'no_show']) : 'registered',
                ]);
            }
        }

        // Create staff assignments
        $staff = User::whereNull('track_id')->take(3)->get();
        foreach ([$jobFair, $techEvent] as $event) {
            foreach ($staff as $staffMember) {
                EventStaffAssignment::create([
                    'event_id' => $event->id,
                    'user_id' => $staffMember->id,
                    'role' => fake()->randomElement(['coordinator', 'check-in', 'support']),
                    'assigned_by' => $creator->id,
                ]);
            }
        }

        // Add visibility tracks to all track-based events
        foreach (Event::where('visibility_type', 'track_based')->get() as $event) {
            foreach ($tracks->random(3) as $track) {
                \DB::table('event_visibility_tracks')->insert([
                    'event_id' => $event->id,
                    'track_id' => $track->id,
                    'created_at' => now(),
                ]);
            }
        }

    }
}
