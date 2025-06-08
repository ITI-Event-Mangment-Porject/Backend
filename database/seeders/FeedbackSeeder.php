<?php

namespace Database\Seeders;

use App\Models\Event\Event;
use App\Models\Feedback_and_Analytics\FeedbackForm;
use App\Models\Feedback_and_Analytics\FeedbackResponse;
use App\Models\Auth\User;
use App\Models\AiInsight;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::where('status', 'completed')->get();
        $creator = User::first();
        $students = User::whereNotNull('track_id')->get();

        foreach ($events as $event) {
            // Create feedback form
            $feedbackForm = FeedbackForm::factory()->create([
                'event_id' => $event->id,
                'title' => $event->title . ' - Feedback Form',
                'created_by' => $creator->id,
            ]);

            // Create feedback responses
            foreach ($students->random(rand(15, 40)) as $student) {
                FeedbackResponse::factory()->create([
                    'form_id' => $feedbackForm->id,
                    'user_id' => $student->id,
                    'event_id' => $event->id,
                ]);
            }

            // Create AI insights
            $avgRating = fake()->randomFloat(2, 3.5, 4.8);
            $themes = [
                'Great organization',
                'Excellent speakers',
                'Good networking opportunities',
                'Need better timing',
                'More hands-on activities needed'
            ];

            \DB::table('ai_insights')->insert([
                'event_id' => $event->id,
                'insight_type' => 'feedback_summary',
                'data' => json_encode([
                    'total_responses' => rand(15, 40),
                    'response_rate' => rand(60, 85) . '%',
                    'sentiment_distribution' => [
                        'positive' => rand(60, 80),
                        'neutral' => rand(15, 25),
                        'negative' => rand(5, 15)
                    ]
                ]),
                'satisfaction_score' => $avgRating,
                'key_themes' => json_encode(fake()->randomElements($themes, 3)),
                'recommendations' => fake()->paragraph(),
                'generated_at' => now(),
            ]);
        }
    }
}
