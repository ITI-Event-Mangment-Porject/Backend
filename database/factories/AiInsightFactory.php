<?php

namespace Database\Factories;

use App\Models\Event\Event;
use App\Models\FeedbackAndAnalytics\AiInsight;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiInsightFactory extends Factory
{
    protected $model = AiInsight::class;

    public function definition(): array
    {
        $satisfactionScore = $this->faker->randomFloat(2, 1.0, 5.0);
        
        return [
            'event_id' => Event::factory(),
            'insight_type' => $this->faker->randomElement([
                'feedback_summary', 
                'attendance_analysis', 
                'engagement_metrics'
            ]),
            'data' => [
                'overall_satisfaction' => round($satisfactionScore * 20) . '% (' . $satisfactionScore . '/5)',
                'key_strengths' => $this->faker->randomElements([
                    'Excellent speaker quality',
                    'Well-organized event',
                    'Relevant content',
                    'Good networking opportunities',
                    'Professional venue',
                    'Timely execution'
                ], $this->faker->numberBetween(2, 4)),
                'areas_for_improvement' => $this->faker->randomElements([
                    'Better time management',
                    'More interactive sessions',
                    'Improved catering',
                    'Better audio/visual setup',
                    'More diverse topics',
                    'Extended Q&A sessions'
                ], $this->faker->numberBetween(2, 3)),
                'common_themes' => $this->faker->randomElements([
                    'Professional development',
                    'Technical skills',
                    'Career guidance',
                    'Industry insights',
                    'Networking',
                    'Innovation'
                ], $this->faker->numberBetween(3, 5)),
                'sentiment_analysis' => $this->faker->randomElement([
                    'Positive - Attendees were highly satisfied',
                    'Neutral - Mixed feedback with room for improvement',
                    'Positive - Generally well-received event'
                ]),
                'recommendations' => [
                    [
                        'priority' => 'high',
                        'action' => $this->faker->sentence(),
                        'impact' => 'Improved attendee satisfaction',
                        'implementation' => $this->faker->sentence()
                    ],
                    [
                        'priority' => 'medium',
                        'action' => $this->faker->sentence(),
                        'impact' => 'Better event organization',
                        'implementation' => $this->faker->sentence()
                    ]
                ],
                'summary' => $this->faker->paragraph(2),
                'attendance_insights' => $this->faker->sentence(),
                'technical_feedback' => $this->faker->sentence()
            ],
            'satisfaction_score' => $satisfactionScore,
            'key_themes' => $this->faker->randomElements([
                'Professional Development',
                'Technical Skills',
                'Career Guidance',
                'Industry Insights',
                'Networking',
                'Innovation',
                'Leadership',
                'Communication'
            ], $this->faker->numberBetween(3, 6)),
            'recommendations' => $this->faker->paragraph(),
            'generated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create an insight for a completed event with high satisfaction
     */
    public function highSatisfaction(): static
    {
        return $this->state(function (array $attributes) {
            $score = $this->faker->randomFloat(2, 4.0, 5.0);
            return [
                'satisfaction_score' => $score,
                'data' => array_merge($attributes['data'] ?? [], [
                    'overall_satisfaction' => round($score * 20) . '% - Excellent feedback',
                    'sentiment_analysis' => 'Positive - Attendees were highly satisfied with the event quality and content'
                ])
            ];
        });
    }

    /**
     * Create an insight for an event that needs improvement
     */
    public function needsImprovement(): static
    {
        return $this->state(function (array $attributes) {
            $score = $this->faker->randomFloat(2, 1.5, 3.0);
            return [
                'satisfaction_score' => $score,
                'data' => array_merge($attributes['data'] ?? [], [
                    'overall_satisfaction' => round($score * 20) . '% - Below expectations',
                    'sentiment_analysis' => 'Neutral to Negative - Several areas identified for improvement'
                ])
            ];
        });
    }
}