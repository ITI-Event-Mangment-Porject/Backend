<?php

namespace Database\Factories;

use App\Models\RegistrationAndInterview\InterviewRequest;
use App\Models\Event\Event;
use App\Models\Auth\User;
use App\Models\JobFair\JobProfile;
use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class InterviewRequestFactory extends Factory
{
    protected $model = InterviewRequest::class;

    public function definition(): array
    {
        $statuses = ['pending', 'approved', 'rejected'];
        $status = $this->faker->randomElement($statuses);

        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'job_profile_id' => JobProfile::factory(),
            'company_id' => Company::factory(),
            'status' => $status,
            'message' => $this->faker->optional()->paragraph(),
            'requested_at' => $this->faker->dateTimeBetween('-1 month'),
            'reviewed_at' => $status !== 'pending' ? $this->faker->dateTimeBetween('-2 weeks') : null,
            'reviewed_by' => $status !== 'pending' ? User::factory() : null,
            'notes' => $status !== 'pending' ? $this->faker->optional()->sentence() : null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_at' => $this->faker->dateTimeBetween('-2 weeks'),
            'reviewed_by' => User::factory(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);
    }
}