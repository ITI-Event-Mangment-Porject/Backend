<?php

namespace App\Http\Controllers;

use App\Models\Event\Event;
use App\Models\FeedbackAndAnalytics\AiInsight;
use App\Services\AIFeedbackAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AIInsightsController extends Controller
{
    private AIFeedbackAnalysisService $aiService;

    public function __construct(AIFeedbackAnalysisService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate AI insights for a specific event
     */
    public function generateInsights(Request $request, $eventId): JsonResponse
    {
        try {
            // Validate that Groq API key is configured
            if (!env('GROQ_API_KEY')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Groq API key not configured. Please set GROQ_API_KEY in your .env file'
                ], 500);
            }

            $event = Event::findOrFail($eventId);
            $regenerate = $request->boolean('regenerate', false);
            
            $result = $this->aiService->generateInsights($event, $regenerate);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get AI insights for a specific event
     */
    public function getInsights($eventId): JsonResponse
    {
        try {
            $event = Event::findOrFail($eventId);
            $insights = $this->aiService->getEventInsights($event);
            
            if (!$insights) {
                return response()->json([
                    'success' => false,
                    'message' => 'No AI insights found for this event'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'event' => [
                        'id' => $event->id,
                        'title' => $event->title,
                        'date' => $event->start_date
                    ],
                    'insights' => [
                        'satisfaction_score' => $insights->satisfaction_score,
                        'key_strengths' => json_decode($insights->key_strengths),
                        'areas_for_improvement' => json_decode($insights->areas_for_improvement),
                        'common_themes' => json_decode($insights->common_themes),
                        'sentiment_analysis' => $insights->sentiment_analysis,
                        'recommendations' => json_decode($insights->recommendations),
                        'summary' => $insights->summary,
                        'attendance_insights' => $insights->attendance_insights,
                        'technical_feedback' => $insights->technical_feedback,
                        'feedback_count' => $insights->feedback_count,
                        'generated_at' => $insights->generated_at
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get all AI insights
     */
    public function getAllInsights(): JsonResponse
    {
        try {
            $insights = $this->aiService->getAllInsights();
            
            return response()->json([
                'success' => true,
                'data' => $insights->map(function ($insight) {
                    return [
                        'id' => $insight->id,
                        'event' => [
                            'id' => $insight->event->id,
                            'title' => $insight->event->title,
                            'date' => $insight->event->start_date
                        ],
                        'satisfaction_score' => $insight->satisfaction_score,
                        'summary' => $insight->summary,
                        'feedback_count' => $insight->feedback_count,
                        'generated_at' => $insight->generated_at
                    ];
                })
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete AI insights for a specific event
     */
    public function deleteInsights($eventId): JsonResponse
    {
        try {
            $event = Event::findOrFail($eventId);
            $deleted = AiInsight::where('event_id', $event->id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Deleted {$deleted} insight(s) for event: {$event->title}"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
