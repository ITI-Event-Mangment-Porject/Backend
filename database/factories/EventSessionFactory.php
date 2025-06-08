<?php

namespace Database\Factories;

use App\Models\Event\EventSession;
use App\Models\Event\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventSessionFactory extends Factory
{
    protected $model = EventSession::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'speaker_name' => $this->faker->optional()->name(),
            'speaker_bio' => $this->faker->optional()->paragraph(2),
            'speaker_image' => $this->faker->optional()->imageUrl(200, 200, 'people'),
            'start_time' => $this->faker->time('H:i:s'),
            'end_time' => $this->faker->time('H:i:s'),
            'location' => $this->faker->optional()->address(),
            'session_order' => $this->faker->numberBetween(1, 10),
            'is_break' => $this->faker->boolean(20),
        ];
    }

    public function breakSession(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $this->faker->randomElement(['Coffee Break', 'Lunch Break', 'Prayer Break']),
            'is_break' => true,
            'speaker_name' => null,
            'speaker_bio' => null,
            'speaker_image' => null,
        ]);
    }
}
