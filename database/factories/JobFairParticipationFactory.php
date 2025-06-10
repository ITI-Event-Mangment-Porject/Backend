<?php

namespace Database\Factories;

use App\Models\JobFair\JobFairParticipation;
use App\Models\Event\Event;
use App\Models\Company\Company;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFairParticipationFactory extends Factory
{
    protected $model = JobFairParticipation::class;

    public function definition(): array
    {
        $statuses = ['pending', 'approved', 'rejected'];
        $status = $this->faker->randomElement($statuses);

        return [
            'event_id' => Event::factory()->jobFair(),
            'company_id' => Company::factory(),
            'status' => $status,
            'special_requirements' => $this->faker->optional()->paragraph(),
            'submitted_by' => User::factory(),
            'submitted_at' => $this->faker->dateTimeBetween('-1 month'),
            'reviewed_by' => $status !== 'pending' ? User::factory() : null,
            'reviewed_at' => $status !== 'pending' ? $this->faker->dateTimeBetween('-2 weeks') : null,
            'review_notes' => $status !== 'pending' ? $this->faker->optional()->sentence() : null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_by' => User::factory(),
            'reviewed_at' => $this->faker->dateTimeBetween('-2 weeks'),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }
}