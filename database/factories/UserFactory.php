<?php

namespace Database\Factories;

use App\Models\Auth\User;
use App\Models\Auth\Track;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'portal_user_id' => 'PU' . $this->faker->unique()->bothify('#####'),
            'email' => $this->faker->unique()->safeEmail(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'phone' => $this->faker->phoneNumber(),
            'profile_image' => $this->faker->optional()->imageUrl(200, 200, 'people'),
            'cv_path' => $this->faker->optional()->filePath(),
            'bio' => $this->faker->optional()->paragraph(3),
            'linkedin_url' => $this->faker->optional()->url(),
            'github_url' => $this->faker->optional()->url(),
            'portfolio_url' => $this->faker->optional()->url(),
            'track_id' => Track::inRandomOrder()->first()->id ?? Track::factory()->create()->id,
            'intake_year' => $this->faker->optional()->numberBetween(2020, 2024),
            'graduation_year' => $this->faker->optional()->numberBetween(2024, 2026),
            'is_active' => $this->faker->boolean(95),
            'last_login_at' => $this->faker->optional()->dateTimeBetween('-1 month'),
        ];
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'intake_year' => $this->faker->numberBetween(2020, 2024),
            'graduation_year' => $this->faker->numberBetween(2024, 2026),
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_id' => null,
            'intake_year' => null,
            'graduation_year' => null,
        ]);
    }
}
