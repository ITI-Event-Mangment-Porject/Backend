<?php
namespace Database\Factories;

use App\Models\Job_Fair\JobProfile;
use App\Models\Job_Fair\JobFairParticipation;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobProfileFactory extends Factory
{
    protected $model = JobProfile::class;

    public function definition(): array
    {
        $employmentTypes = ['Full-time', 'Part-time', 'Internship', 'Contract'];
        
        $jobTitles = [
            'Software Developer', 'Frontend Developer', 'Backend Developer',
            'Data Scientist', 'UI/UX Designer', 'Product Manager',
            'DevOps Engineer', 'QA Engineer', 'Business Analyst',
            'Cybersecurity Specialist', 'Digital Marketing Specialist'
        ];

        return [
            'participation_id' => JobFairParticipation::factory(),
            'title' => $this->faker->randomElement($jobTitles),
            'description' => $this->faker->paragraphs(2, true),
            'requirements' => $this->faker->paragraphs(3, true),
            'employment_type' => $this->faker->randomElement($employmentTypes),
            'location' => $this->faker->optional()->city(),
            'positions_available' => $this->faker->numberBetween(1, 5),
        ];
    }

    public function internship(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'Internship',
            'title' => $this->faker->randomElement([
                'Software Development Intern',
                'Data Science Intern',
                'UI/UX Design Intern',
                'Marketing Intern'
            ]),
        ]);
    }

    public function fullTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'Full-time',
        ]);
    }
}