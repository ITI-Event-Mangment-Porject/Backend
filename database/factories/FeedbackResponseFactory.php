<?php

namespace Database\Factories;

use App\Models\FeedbackAndAnalytics\FeedbackResponse;
use App\Models\FeedbackAndAnalytics\FeedbackForm;
use App\Models\Auth\User;
use App\Models\Event\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedbackResponseFactory extends Factory
{
    protected $model = FeedbackResponse::class;

    public function definition(): array
    {
        $responses = [
            'overall_rating' => $this->faker->numberBetween(1, 5),
            'liked_most' => $this->faker->sentence(),
            'improvements' => $this->faker->sentence(),
            'organization_rating' => $this->faker->numberBetween(1, 5)
        ];

        return [
            'form_id' => FeedbackForm::factory(),
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'responses' => json_encode($responses),
            'overall_rating' => $this->faker->numberBetween(1, 5),
            'submitted_at' => $this->faker->dateTimeBetween('-1 month'),
        ];
    }
}