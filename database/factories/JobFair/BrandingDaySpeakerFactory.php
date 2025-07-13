<?php

namespace Database\Factories\JobFair;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobFair\BrandingDaySpeaker>
 */
class BrandingDaySpeakerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'speaker_name' => $this->faker->name(),
            'position' => $this->faker->jobTitle(),
            'mobile' => $this->faker->phoneNumber(),
            'photo' => 'speakers/placeholder.jpg', // Placeholder for a stored image file path
        ];
    }
}
