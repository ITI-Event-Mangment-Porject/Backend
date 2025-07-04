<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseApiController;
use App\Services\AIFeedbackAnalysisService;
use App\Models\Event\Event;
use App\Models\FeedbackAndAnalytics\AiInsight;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

class AIInsightsController extends BaseApiController
{
    private AIFeedbackAnalysisService $aiService;

    public function __construct(AIFeedbackAnalysisService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate AI insights for an event's feedback
     */
    public function generateInsights(Request $request, int $eventId): JsonResponse
    {
        try {
            $regenerate = $request->boolean('regenerate', false);
            
            if ($regenerate) {
                $report = $this->aiService->regenerateInsights($eventId);
            } else {
                $report = $this->aiService->generateEventFeedbackReport($eventId);
            }
            
            return $this->sendResponse($report, 'AI insights generated successfully', 201);
            
        } catch (\Exception $e) {
            return $this->sendError('Failed to generate insights: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get AI insights for an event
     */
    public function show(int $eventId): JsonResponse
    {
        try {
            $event = Event::findOrFail($eventId);
            $insights = $this->aiService->getEventInsights($eventId);
            
            if (!$insights) {
                return $this->sendError('No AI insights available for this event', [], 404);
            }

            $data = json_decode($insights->data, true);
            
            $response = [
                'event' => $event->only(['id', 'title', 'type', 'start_date', 'end_date', 'status']),
                'insights' => $data,
                'satisfaction_score' => $insights->satisfaction_score,
                'generated_at' => $insights->generated_at,
                'key_themes' => json_decode($insights->key_themes, true),
                'total_responses' => $event->feedbackResponses()->count()
            ];
            
            return $this->sendResponse($response, 'AI insights retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve insights: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get all events with their latest AI insights (Admin only)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 10);
            
            $eventsQuery = QueryBuilder::for(Event::class)
                ->allowedFilters([
                    AllowedFilter::exact('type'),
                    AllowedFilter::exact('status'),
                    AllowedFilter::partial('title'),
                    AllowedFilter::scope('has_feedback', function ($query) {
                        $query->whereHas('feedbackResponses');
                    }),
                    AllowedFilter::scope('has_insights', function ($query) {
                        $query->whereHas('aiInsights');
                    }),
                ])
                ->allowedSorts([
                    'title',
                    'start_date',
                    'created_at',
                    AllowedSort::field('feedback_count', function ($query, $direction) {
                        $query->withCount('feedbackResponses')
                              ->orderBy('feedback_responses_count', $direction);
                    }),
                ])
                ->with(['aiInsights' => function ($query) {
                    $query->where('insight_type', 'feedback_summary')
                          ->latest('generated_at')
                          ->limit(1);
                }])
                ->withCount('feedbackResponses')
                ->whereHas('feedbackResponses');

            $events = $eventsQuery->paginate($perPage);
            
            $transformedEvents = $events->getCollection()->map(function ($event) {
                $latestInsight = $event->aiInsights->first();
                
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'type' => $event->type,
                    'status' => $event->status,
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'feedback_count' => $event->feedback_responses_count,
                    'has_insights' => $latestInsight !== null,
                    'satisfaction_score' => $latestInsight?->satisfaction_score,
                    'last_analysis' => $latestInsight?->generated_at,
                    'insight_summary' => $latestInsight ? 
                        json_decode($latestInsight->data, true)['summary'] ?? 'No summary available' : 
                        null
                ];
            });

            $events->setCollection($transformedEvents);
            
            return $this->sendResponse($events, 'Events with insights retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve events: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Update/approve AI insights (Admin only)
     */
    public function update(Request $request, int $insightId): JsonResponse
    {
        try {
            $request->validate([
                'recommendations' => 'sometimes|string|max:2000',
                'notes' => 'sometimes|string|max:1000',
                'approved' => 'sometimes|boolean'
            ]);

            $insight = AiInsight::findOrFail($insightId);
            
            if ($request->has('recommendations')) {
                $insight->recommendations = $request->recommendations;
            }
            
            if ($request->has('approved')) {
                $data = json_decode($insight->data, true);
                $data['admin_approved'] = $request->approved;
                $data['approved_at'] = now()->toISOString();
                $data['approved_by'] = auth()->id();
                $insight->data = json_encode($data);
            }
            
            $insight->save();

            return $this->sendResponse($insight, 'Insights updated successfully');
            
        } catch (\Exception $e) {
            return $this->sendError('Failed to update insights: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Delete AI insights (Admin only)
     */
    public function destroy(int $insightId): JsonResponse
    {
        try {
            $insight = AiInsight::findOrFail($insightId);
            $insight->delete();
            
            return $this->sendResponse([], 'Insights deleted successfully');
            
        } catch (\Exception $e) {
            return $this->sendError('Failed to delete insights: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get insights trends and analytics (Admin only)
     */
    public function trends(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // week, month, quarter, year
            $eventType = $request->get('type');
            
            $startDate = match($period) {
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                'quarter' => now()->subQuarter(),
                'year' => now()->subYear(),
                default => now()->subMonth()
            };

            $query = AiInsight::with('event')
                ->where('generated_at', '>=', $startDate)
                ->where('insight_type', 'feedback_summary');

            if ($eventType) {
                $query->whereHas('event', function ($q) use ($eventType) {
                    $q->where('type', $eventType);
                });
            }

            $insights = $query->get();
            
            if ($insights->isEmpty()) {
                return $this->sendResponse([
                    'period' => $period,
                    'event_type' => $eventType,
                    'message' => 'No insights found for the specified period'
                ], 'No data available');
            }

            // Calculate trends
            $avgSatisfaction = $insights->whereNotNull('satisfaction_score')
                ->avg('satisfaction_score');
            
            // Extract common themes
            $allThemes = [];
            foreach ($insights as $insight) {
                $themes = json_decode($insight->key_themes, true) ?? [];
                $allThemes = array_merge($allThemes, $themes);
            }
            
            $themeFrequency = array_count_values($allThemes);
            arsort($themeFrequency);
            
            // Best and worst performing events
            $bestEvent = $insights->sortByDesc('satisfaction_score')->first();
            $worstEvent = $insights->sortBy('satisfaction_score')->first();
            
            $trendsData = [
                'period' => $period,
                'event_type' => $eventType,
                'total_events_analyzed' => $insights->count(),
                'average_satisfaction' => round($avgSatisfaction, 2),
                'common_themes' => array_slice($themeFrequency, 0, 10, true),
                'best_performing_event' => $bestEvent ? [
                    'id' => $bestEvent->event->id,
                    'title' => $bestEvent->event->title,
                    'satisfaction_score' => $bestEvent->satisfaction_score,
                    'date' => $bestEvent->event->start_date
                ] : null,
                'needs_attention_event' => $worstEvent && $worstEvent->id !== $bestEvent?->id ? [
                    'id' => $worstEvent->event->id,
                    'title' => $worstEvent->event->title,
                    'satisfaction_score' => $worstEvent->satisfaction_score,
                    'date' => $worstEvent->event->start_date
                ] : null,
                'satisfaction_distribution' => $this->getSatisfactionDistribution($insights)
            ];
            
            return $this->sendResponse($trendsData, 'Trends analysis retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->sendError('Failed to analyze trends: ' . $e->getMessage(), [], 500);
        }
    }

    private function getSatisfactionDistribution($insights): array
    {
        $distribution = [
            'excellent' => 0, // 4.5-5.0
            'good' => 0,      // 3.5-4.4
            'average' => 0,   // 2.5-3.4
            'poor' => 0,      // 1.5-2.4
            'very_poor' => 0  // 0-1.4
        ];

        foreach ($insights as $insight) {
            if ($insight->satisfaction_score === null) continue;
            
            $score = $insight->satisfaction_score;
            if ($score >= 4.5) $distribution['excellent']++;
            elseif ($score >= 3.5) $distribution['good']++;
            elseif ($score >= 2.5) $distribution['average']++;
            elseif ($score >= 1.5) $distribution['poor']++;
            else $distribution['very_poor']++;
        }

        return $distribution;
    }
}