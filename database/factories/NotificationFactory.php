<?php

namespace Database\Factories;

use App\Models\Notifications_and_Messaging\Notification;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $types = ['registration', 'interview_update', 'queue_status', 
                 'feedback_reminder', 'general', 'system'];
        $relatedTypes = ['event', 'interview', 'company', null];

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'message' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement($types),
            'related_id' => $this->faker->optional()->numberBetween(1, 100),
            'related_type' => $this->faker->randomElement($relatedTypes),
            'is_read' => $this->faker->boolean(30),
            'sent_via' => json_encode(['in-app', 'email']),
            'read_at' => function (array $attributes) {
                return $attributes['is_read'] ? $this->faker->dateTimeBetween('-1 week') : null;
            },
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => $this->faker->dateTimeBetween('-1 week'),
        ]);
    }
}