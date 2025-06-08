<?php
namespace Database\Factories;

use App\Models\Registration_and_interview\EventRegistration;
use App\Models\Event\Event;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventRegistrationFactory extends Factory
{
    protected $model = EventRegistration::class;

    public function definition(): array
    {
        $statuses = ['registered', 'cancelled', 'attended', 'no_show'];
        $status = $this->faker->randomElement($statuses);

        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'status' => $status,
            'registration_type' => $this->faker->randomElement(['auto', 'manual']),
            'registered_at' => $this->faker->dateTimeBetween('-1 month'),
            'cancelled_at' => $status === 'cancelled' ? $this->faker->dateTimeBetween('-2 weeks') : null,
            'cancellation_reason' => $status === 'cancelled' ? $this->faker->optional()->sentence() : null,
            'checked_in_at' => in_array($status, ['attended', 'no_show']) ? 
                $this->faker->optional()->dateTimeBetween('-1 week') : null,
            'checked_in_by' => in_array($status, ['attended']) ? User::factory() : null,
            'check_in_method' => in_array($status, ['attended']) ? 
                $this->faker->randomElement(['qr', 'manual']) : null,
        ];
    }

    public function attended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'attended',
            'checked_in_at' => $this->faker->dateTimeBetween('-1 week'),
            'checked_in_by' => User::factory(),
            'check_in_method' => $this->faker->randomElement(['qr', 'manual']),
        ]);
    }

    public function registered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'registered',
        ]);
    }
}
