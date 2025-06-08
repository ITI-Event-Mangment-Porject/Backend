<?php

namespace Database\Factories;

use App\Models\Auth\Track;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TrackFactory extends Factory
{
    protected $model = Track::class;

    public function definition(): array
    {
        // Reset unique cache to avoid overflow on repeated calls
        $this->faker->unique($reset = true);

        // Generate a unique two-word name and slug
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
            'color' => $this->faker->hexColor(),
            'icon' => 'code', // or random icon if you want
            'is_active' => $this->faker->boolean(90),
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
