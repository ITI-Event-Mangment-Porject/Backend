<?php

namespace Database\Factories;

use App\Models\JobFair\JobFairParticipation;
use App\Models\JobFair\InterviewSlot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class InterviewSlotFactory extends Factory
{
    protected $model = InterviewSlot::class;

    public function definition(): array
    {
        $duration = $this->faker->randomElement([15, 20, 30]);

        // Generate start time between 08:00 and 17:45 in 15-min increments
        $startHour = $this->faker->numberBetween(8, 17);
        $startMinute = $this->faker->randomElement([0, 15, 30, 45]);

        $start = Carbon::createFromTime($startHour, $startMinute, 0);
        $end = $start->copy()->addMinutes($duration)->setSecond(0);

        // Fix edge case: if end time has 60 minutes, convert to next hour
        if ($end->minute === 60) {
            $end->addHour()->minute(0);
        }

        // Prevent end time from exceeding 23:59
        if ($end->greaterThan(Carbon::createFromTime(23, 59))) {
            $end = Carbon::createFromTime(23, 59);
            $duration = $start->diffInMinutes($end);
        }

        $isBreak = $this->faker->boolean(10); // 10% chance of break

        return [
            'participation_id' => JobFairParticipation::factory(),
            'slot_date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
            'duration_minutes' => $duration,
            'max_interviews_per_slot' => $this->faker->numberBetween(1, 3),
            'is_break' => $isBreak,
            'break_reason' => $isBreak ? $this->faker->randomElement(['lunch', 'prayer', 'coffee']) : null,
            'is_available' => !$isBreak,
        ];
    }

    public function breakSlot(): static
    {
        return $this->state(function (array $attributes) {
            $startHour = $this->faker->numberBetween(8, 17);
            $startMinute = $this->faker->randomElement([0, 15, 30, 45]);

            $start = Carbon::createFromTime($startHour, $startMinute, 0);
            $end = $start->copy()->addMinutes(15)->setSecond(0);

            if ($end->minute === 60) {
                $end->addHour()->minute(0);
            }

            return [
                'start_time' => $start->format('H:i:s'),
                'end_time' => $end->format('H:i:s'),
                'duration_minutes' => 15,
                'is_break' => true,
                'break_reason' => $this->faker->randomElement(['lunch', 'prayer', 'coffee']),
                'is_available' => false,
            ];
        });
    }
}
