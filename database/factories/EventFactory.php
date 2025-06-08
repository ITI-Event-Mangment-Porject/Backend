<?php

namespace Database\Factories;

use App\Models\Event\Event;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $types = ['Job Fair', 'Tech', 'Fun'];
        $statuses = ['draft', 'published', 'ongoing', 'completed', 'archived'];
        $visibilityTypes = ['all', 'role_based', 'track_based'];

        $title = $this->faker->sentence(3);
        $startDate = $this->faker->dateTimeBetween('now', '+3 months');
        $endDate = $this->faker->dateTimeBetween($startDate, $startDate->format('Y-m-d') . ' +7 days');

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'description' => $this->faker->paragraphs(3, true),
            'type' => $this->faker->randomElement($types),
            'status' => $this->faker->randomElement($statuses),
            'location' => $this->faker->address(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'start_time' => $this->faker->time('H:i:s'),
            'end_time' => $this->faker->time('H:i:s'),
            'banner_image' => $this->faker->optional()->imageUrl(1200, 600, 'business'),
            'registration_deadline' => $this->faker->optional()->dateTimeBetween('now', $startDate),
            'visibility_type' => $this->faker->randomElement($visibilityTypes),
            'visibility_config' => function (array $attributes) {
                if ($attributes['visibility_type'] === 'role_based') {
                    return json_encode(['roles' => ['student', 'alumni']]);
                } elseif ($attributes['visibility_type'] === 'track_based') {
                    return json_encode(['tracks' => [1, 2, 3]]);
                }
                return null;
            },
            'slido_qr_code' => $this->faker->optional()->imageUrl(300, 300, 'abstract'),
            'slido_embed_url' => $this->faker->optional()->url(),
            'created_by' => User::factory(),
        ];
    }

    public function jobFair(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Job Fair',
        ]);
    }

    public function techEvent(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Tech',
        ]);
    }

    public function funEvent(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Fun',
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }
}