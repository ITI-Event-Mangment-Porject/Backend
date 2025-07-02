<?php

namespace App\Services;

use App\Models\Event;
use App\Models\AiInsight;
use App\Models\FeedbackResponse;
use App\Models\EventRegistration;
use App\Models\InterviewQueue;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIInsightService
{
    public function generateInsight(Event $event, string $insightType, bool $forceRegenerate = false): AiInsight
    {
        $cacheKey = "ai_insight_{$event->id}_{$insightType}";
        
        if (!$forceRegenerate && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $data = $this->prepareDataForInsight($event, $insightType);
        $aiResponse = $this->callOpenAI($data, $insightType);
        
        $insight = AiInsight::updateOrCreate(
            [
                'event_id' => $event->id,
                'insight_type' => $insightType
            ],
            [
                'data' => $data,
                'satisfaction_score' => $aiResponse['satisfaction_score'] ?? null,
                'key_themes' => $aiResponse['key_themes'] ?? [],
                'recommendations' => $aiResponse['recommendations'] ?? '',
                'ai_summary' => $aiResponse['summary'] ?? '',
                'generated_at' => now()
            ]
        );

        Cache::put($cacheKey, $insight, now()->addHours(6));
        
        return $insight;
    }

    protected function prepareDataForInsight(Event $event, string $insightType): array
    {
        switch ($insightType) {
            case 'feedback_summary':
                return $this->prepareFeedbackData($event);
            
            case 'attendance_analysis':
                return $this->prepareAttendanceData($event);
            
            case 'engagement_metrics':
                return $this->prepareEngagementData($event);
            
            default:
                throw new \InvalidArgumentException("Unknown insight type: $insightType");
        }
    }

    protected function prepareFeedbackData(Event $event): array
    {
        $feedbacks = FeedbackResponse::where('event_id', $event->id)
            ->with('form')
            ->get();

        $ratings = $feedbacks->pluck('overall_rating')->filter()->toArray();
        $comments = $feedbacks->pluck('responses')
            ->flatten()
            ->filter(function ($response) {
                return is_string($response) && strlen($response) > 10;
            })
            ->toArray();

        return [
            'event_type' => $event->type,
            'event_title' => $event->title,
            'total_responses' => $feedbacks->count(),
            'average_rating' => !empty($ratings) ? round(array_sum($ratings) / count($ratings), 2) : 0,
            'rating_distribution' => array_count_values($ratings),
            'comments' => array_slice($comments, 0, 50), // Limit to 50 comments
            'response_rate' => $this->calculateResponseRate($event, $feedbacks->count())
        ];
    }

    protected function prepareAttendanceData(Event $event): array
    {
        $registrations = EventRegistration::where('event_id', $event->id)->get();
        $totalRegistered = $registrations->count();
        $totalAttended = $registrations->where('status', 'attended')->count();
        $noShows = $registrations->where('status', 'no_show')->count();

        return [
            'event_type' => $event->type,
            'event_title' => $event->title,
            'total_registered' => $totalRegistered,
            'total_attended' => $totalAttended,
            'no_shows' => $noShows,
            'attendance_rate' => $totalRegistered > 0 ? round(($totalAttended / $totalRegistered) * 100, 2) : 0,
            'registration_timeline' => $this->getRegistrationTimeline($registrations),
            'track_breakdown' => $this->getTrackBreakdown($registrations)
        ];
    }

    protected function prepareEngagementData(Event $event): array
    {
        $data = [
            'event_type' => $event->type,
            'event_title' => $event->title,
        ];

        if ($event->type === 'Job Fair') {
            $interviewData = InterviewQueue::whereHas('interviewRequest', function ($query) use ($event) {
                $query->where('event_id', $event->id);
            })->get();

            $data['interview_metrics'] = [
                'total_interviews' => $interviewData->count(),
                'completed_interviews' => $interviewData->where('status', 'completed')->count(),
                'average_wait_time' => $this->calculateAverageWaitTime($interviewData),
                'no_show_rate' => $this->calculateNoShowRate($interviewData)
            ];
        }

        return $data;
    }

    protected function callOpenAI(array $data, string $insightType): array
    {
        $prompt = $this->buildPrompt($data, $insightType);
        
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert event analyst for an IT education platform. Analyze the provided data and return insights in JSON format.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1500,
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object']
            ]);

            $content = $response->choices[0]->message->content;
            return json_decode($content, true) ?? [];

        } catch (\Exception $e) {
            Log::error('OpenAI API Error', [
                'insight_type' => $insightType,
                'error' => $e->getMessage()
            ]);
            
            return $this->getFallbackInsights($data, $insightType);
        }
    }

    protected function buildPrompt(array $data, string $insightType): string
    {
        $basePrompt = "Analyze this {$insightType} data for an IT event and provide insights.\n\nData: " . json_encode($data, JSON_PRETTY_PRINT);

        switch ($insightType) {
            case 'feedback_summary':
                return $basePrompt . "\n\nPlease provide a JSON response with:
                - satisfaction_score: Overall satisfaction (0-5)
                - key_themes: Array of main themes from feedback
                - recommendations: Specific actionable recommendations
                - summary: Brief summary of feedback trends";

            case 'attendance_analysis':
                return $basePrompt . "\n\nPlease provide a JSON response with:
                - attendance_insights: Key insights about attendance patterns
                - recommendations: Ways to improve attendance
                - summary: Brief attendance analysis
                - risk_factors: Potential issues identified";

            case 'engagement_metrics':
                return $basePrompt . "\n\nPlease provide a JSON response with:
                - engagement_score: Overall engagement level (0-5)
                - key_metrics: Important engagement indicators
                - recommendations: Ways to improve engagement
                - summary: Brief engagement analysis";

            default:
                return $basePrompt;
        }
    }

    protected function getFallbackInsights(array $data, string $insightType): array
    {
        // Provide basic statistical insights when AI fails
        switch ($insightType) {
            case 'feedback_summary':
                return [
                    'satisfaction_score' => $data['average_rating'] ?? 0,
                    'key_themes' => ['General feedback collected'],
                    'recommendations' => 'Review individual feedback responses for detailed insights.',
                    'summary' => "Collected {$data['total_responses']} feedback responses with an average rating of {$data['average_rating']}."
                ];

            default:
                return [
                    'summary' => 'Basic statistical analysis completed.',
                    'recommendations' => 'Manual review recommended for detailed insights.'
                ];
        }
    }

    // Helper methods
    protected function calculateResponseRate(Event $event, int $responseCount): float
    {
        $totalAttended = EventRegistration::where('event_id', $event->id)
            ->where('status', 'attended')
            ->count();
            
        return $totalAttended > 0 ? round(($responseCount / $totalAttended) * 100, 2) : 0;
    }

    protected function getRegistrationTimeline(Collection $registrations): array
    {
        return $registrations->groupBy(function ($registration) {
            return $registration->registered_at->format('Y-m-d');
        })->map->count()->toArray();
    }

    protected function getTrackBreakdown(Collection $registrations): array
    {
        return $registrations->load('user.track')
            ->groupBy('user.track.name')
            ->map->count()
            ->toArray();
    }

    protected function calculateAverageWaitTime(Collection $interviewData): float
    {
        $waitTimes = $interviewData->filter(function ($interview) {
            return $interview->interview_started_at && $interview->created_at;
        })->map(function ($interview) {
            return $interview->created_at->diffInMinutes($interview->interview_started_at);
        });

        return $waitTimes->avg() ?? 0;
    }

    protected function calculateNoShowRate(Collection $interviewData): float
    {
        $total = $interviewData->count();
        $noShows = $interviewData->where('status', 'no_show')->count();
        
        return $total > 0 ? round(($noShows / $total) * 100, 2) : 0;
    }
}