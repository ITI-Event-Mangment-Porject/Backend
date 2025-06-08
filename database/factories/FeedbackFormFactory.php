<?php

namespace Database\Factories;

use App\Models\Feedback_and_Analytics\FeedbackForm;
use App\Models\Event\Event;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedbackFormFactory extends Factory
{
    protected $model = FeedbackForm::class;

    public function definition(): array
    {
        $formConfig = [
            'fields' => [
                [
                    'type' => 'rating',
                    'label' => 'Overall Event Rating',
                    'required' => true,
                    'min' => 1,
                    'max' => 5
                ],
                [
                    'type' => 'text',
                    'label' => 'What did you like most?',
                    'required' => false
                ],
                [
                    'type' => 'text',
                    'label' => 'What could be improved?',
                    'required' => false
                ],
                [
                    'type' => 'rating',
                    'label' => 'Organization Rating',
                    'required' => true,
                    'min' => 1,
                    'max' => 5
                ]
            ]
        ];

        return [
            'event_id' => Event::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'form_config' => json_encode($formConfig),
            'is_active' => $this->faker->boolean(90),
            'created_by' => User::factory(),
        ];
    }
}