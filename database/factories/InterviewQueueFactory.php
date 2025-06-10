<?php

namespace Database\Factories;

use App\Models\RegistrationAndInterview\InterviewQueue;
use App\Models\RegistrationAndInterview\InterviewRequest;
use App\Models\Company\Company;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InterviewQueueFactory extends Factory
{
    protected $model = InterviewQueue::class;

    public function definition(): array
    {
        $statuses = ['waiting', 'in_interview', 'completed', 'no_show', 'cancelled'];
        $status = $this->faker->randomElement($statuses);

        return [
            'interview_request_id' => InterviewRequest::factory(),
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'queue_position' => $this->faker->numberBetween(1, 20),
            'status' => $status,
            'interview_started_at' => in_array($status, ['in_interview', 'completed']) ? 
                $this->faker->dateTimeBetween('-2 hours') : null,
            'interview_ended_at' => $status === 'completed' ? 
                $this->faker->dateTimeBetween('-1 hour') : null,
            'notes' => $this->faker->optional()->paragraph(),
            'updated_by' => User::factory(),
        ];
    }

    public function waiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'waiting',
            'interview_started_at' => null,
            'interview_ended_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'interview_started_at' => $this->faker->dateTimeBetween('-2 hours'),
            'interview_ended_at' => $this->faker->dateTimeBetween('-1 hour'),
        ]);
    }
}
