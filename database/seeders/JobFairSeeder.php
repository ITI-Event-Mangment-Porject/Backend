<?php

namespace Database\Seeders;

use App\Models\Event\Event;
use App\Models\Company\Company;
use App\Models\JobFair\JobFairParticipation;
use App\Models\JobFair\JobProfile;
use App\Models\JobFair\InterviewSlot;
use App\Models\RegistrationAndInterview\InterviewRequest;
use App\Models\RegistrationAndInterview\InterviewQueue;
use App\Models\Auth\User;
use App\Models\Auth\Track;
use App\Models\JobFair\BrandingDaySchedule;
use Illuminate\Database\Seeder;

class JobFairSeeder extends Seeder
{
    public function run(): void
    {
        $jobFairs = Event::where('type', 'Job Fair')->get();
        $companies = Company::where('is_approved', true)->take(15)->get();
        $tracks = Track::all();
        $students = User::whereNotNull('track_id')->get();
        // Get all company representatives
        $companyRepresentatives = User::role('company_representative')->get();
        $reviewer = User::first(); // Assuming reviewer can be any admin/staff user

        // Keep track of which company representative has submitted a participation
        $usedCompanyRepresentatives = collect();

        foreach ($jobFairs as $jobFair) {
            // Create company participations
            foreach ($companies->random(8) as $company) {
                // Randomly decide if this participation needs branding
                $needBranding = fake()->boolean(40); // 40% chance

                // Assign a unique company representative
                $availableRepresentatives = $companyRepresentatives->diff($usedCompanyRepresentatives);
                if ($availableRepresentatives->isEmpty()) {
                    // Refill if all are used, or handle scenario where not enough reps
                    $availableRepresentatives = $companyRepresentatives->shuffle();
                    $usedCompanyRepresentatives = collect();
                }
                $assignedRepresentative = $availableRepresentatives->pop();
                $usedCompanyRepresentatives->push($assignedRepresentative);


                $participation = JobFairParticipation::updateOrCreate(
                    [
                        'event_id' => $jobFair->id,
                        'company_id' => $company->id,
                    ],
                    [
                        'status' => 'approved',
                        'reviewed_by' => $reviewer->id,
                        'reviewed_at' => now()->subDays(rand(5, 15)),
                        'submitted_by' => $assignedRepresentative->id, // Assign unique company rep
                        'need_branding' => $needBranding,
                    ]
                );

                // Create job profiles for each participation
                $jobProfiles = JobProfile::factory(rand(2, 4))->create([
                    'participation_id' => $participation->id,
                ]);

                // Create job profile tracks
                foreach ($jobProfiles as $jobProfile) {
                    foreach ($tracks->random(rand(1, 3)) as $track) {
                        \DB::table('job_profile_tracks')->insert([
                            'job_profile_id' => $jobProfile->id,
                            'track_id' => $track->id,
                            'preference_level' => fake()->randomElement(['required', 'preferred', 'acceptable']),
                            'created_at' => now(),
                        ]);
                    }
                }

                // Create interview slots
                $slotDate = $jobFair->start_date;
                for ($hour = 9; $hour <= 16; $hour++) {
                    for ($minute = 0; $minute < 60; $minute += 30) {
                        InterviewSlot::factory()->create([
                            'participation_id' => $participation->id,
                            'slot_date' => $slotDate,
                            'start_time' => sprintf('%02d:%02d:00', $hour, $minute),
                            'end_time' => \Carbon\Carbon::createFromTime($hour, $minute)->addMinutes(30)->format('H:i:s'),
                            'duration_minutes' => 30,
                        ]);
                    }
                }

                // Create lunch break
                InterviewSlot::factory()->breakSlot()->create([
                    'participation_id' => $participation->id,
                    'slot_date' => $slotDate,
                    'start_time' => '12:00:00',
                    'end_time' => '13:00:00',
                    'break_reason' => 'lunch',
                ]);

                // Create interview requests
                foreach ($students->random(rand(10, 25)) as $student) {
                    $jobProfile = $jobProfiles->random();

                    $request = InterviewRequest::factory()->create([
                        'event_id' => $jobFair->id,
                        'user_id' => $student->id,
                        'job_profile_id' => $jobProfile->id,
                        'company_id' => $company->id,
                        'status' => fake()->randomElement(['approved', 'pending', 'rejected']),
                        'reviewed_by' => $reviewer->id,
                        'reviewed_at' => now()->subDays(rand(1, 10)),
                    ]);

                    // InterviewQueue entries will be created by the automated job/command, not here.
                }

                // Seed BrandingDaySchedule for participations that need branding
                if ($needBranding) {
                    $brandingDaySchedule = BrandingDaySchedule::create([
                        'event_id' => $jobFair->id,
                        'company_id' => $company->id,
                        'participation_id' => $participation->id,
                        'branding_day_date' => $jobFair->start_date,
                        'start_time' => fake()->time('H:i:s', '17:00:00'),
                        'end_time' => fake()->time('H:i:s', '18:00:00'),
                        'order' => rand(1, 20),
                    ]);

                    // Create one BrandingDaySpeaker for this participation
                    $speaker = \App\Models\JobFair\BrandingDaySpeaker::factory()->create([
                        'job_fair_participation_id' => $participation->id,
                    ]);

                    // Update the created BrandingDaySchedule with the speaker_id
                    $brandingDaySchedule->update(['branding_day_speaker_id' => $speaker->id]);
                }
            }

            // Create some pending participations (assign unique company representatives)
            foreach ($companies->random(3) as $company) {
                 // Assign a unique company representative
                $availableRepresentatives = $companyRepresentatives->diff($usedCompanyRepresentatives);
                if ($availableRepresentatives->isEmpty()) {
                    $availableRepresentatives = $companyRepresentatives->shuffle();
                    $usedCompanyRepresentatives = collect();
                }
                $assignedRepresentative = $availableRepresentatives->pop();
                $usedCompanyRepresentatives->push($assignedRepresentative);

                JobFairParticipation::updateOrCreate(
                    [
                        'event_id' => $jobFair->id,
                        'company_id' => $company->id,
                    ],
                    [
                        'status' => 'pending',
                        'submitted_by' => $assignedRepresentative->id, // Assign unique company rep
                    ]
                );
            }
        }
    }
}
