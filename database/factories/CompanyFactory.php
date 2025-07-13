<?php

namespace Database\Factories;

use App\Models\Company\Company;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $industries = [
            'Technology', 'Healthcare', 'Finance', 'Education', 'E-commerce',
            'Gaming', 'Consulting', 'Manufacturing', 'Telecommunications', 'Media'
        ];

        $sizes = ['startup', 'small', 'medium', 'large', 'enterprise'];

        // ✅ Always create a real image file
        $companyName = $this->faker->company();
        $filename = 'logo_' . Str::slug($companyName) . '.png';
        $path = storage_path("app/public/companies/{$filename}");

        $img = imagecreatetruecolor(200, 200);
        $bgColor = imagecolorallocate($img, 220, 220, 220);
        imagefill($img, 0, 0, $bgColor);
        $textColor = imagecolorallocate($img, 0, 0, 0);
        imagestring($img, 5, 30, 90, 'Logo', $textColor);
        imagepng($img, $path);
        imagedestroy($img);

        return [
            'name' => $companyName,
            'logo_path' => "companies/{$filename}",
            'description' => $this->faker->paragraphs(2, true),
            'website' => $this->faker->url(),
            'industry' => $this->faker->randomElement($industries),
            'size' => $this->faker->randomElement($sizes),
            'location' => $this->faker->city() . ', ' . $this->faker->country(),
            'contact_email' => $this->faker->companyEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'linkedin_url' => $this->faker->optional()->url(),
            'is_approved' => $this->faker->boolean(80),
            'approved_by' => function (array $attributes) {
                return $attributes['is_approved'] ? User::factory() : null;
            },
            'approved_at' => function (array $attributes) {
                return $attributes['is_approved'] ? $this->faker->dateTimeBetween('-1 month') : null;
            },
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
            'approved_by' => User::factory(),
            'approved_at' => $this->faker->dateTimeBetween('-1 month'),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }
}
