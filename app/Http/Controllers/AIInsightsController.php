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
            
            // Extract data from the JSON field properly
            $analysisData = $insights->getAnalysisData();
            
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
                        'key_strengths' => $analysisData['key_strengths'] ?? [],
                        'areas_for_improvement' => $analysisData['areas_for_improvement'] ?? [],
                        'common_themes' => $analysisData['common_themes'] ?? [],
                        'sentiment_analysis' => $analysisData['sentiment_analysis'] ?? 'No sentiment analysis available',
                        'recommendations' => $analysisData['recommendations'] ?? [],
                        'summary' => $analysisData['summary'] ?? 'No summary available',
                        'attendance_insights' => $analysisData['attendance_insights'] ?? 'No attendance insights available',
                        'technical_feedback' => $analysisData['technical_feedback'] ?? 'No technical feedback available',
                        'feedback_count' => $analysisData['feedback_count'] ?? 0,
                        'generated_at' => $insights->generated_at,
                        'overall_satisfaction' => $analysisData['overall_satisfaction'] ?? 'Not available',
                        'generated_by' => $analysisData['generated_by'] ?? 'AI Analysis',
                        'admin_approved' => $analysisData['admin_approved'] ?? false
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
                    $analysisData = $insight->getAnalysisData();
                    
                    return [
                        'id' => $insight->id,
                        'event' => [
                            'id' => $insight->event->id,
                            'title' => $insight->event->title,
                            'date' => $insight->event->start_date
                        ],
                        'satisfaction_score' => $insight->satisfaction_score,
                        'summary' => $analysisData['summary'] ?? 'No summary available',
                        'feedback_count' => $analysisData['feedback_count'] ?? 0,
                        'sentiment_analysis' => $analysisData['sentiment_analysis'] ?? 'Not available',
                        'generated_at' => $insight->generated_at,
                        'generated_by' => $analysisData['generated_by'] ?? 'AI Analysis',
                        'admin_approved' => $analysisData['admin_approved'] ?? false
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

    /**
     * Get detailed insights with full analysis data
     */
    public function getDetailedInsights($eventId): JsonResponse
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
            
            // Return complete analysis data
            $analysisData = $insights->getAnalysisData();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'event' => [
                        'id' => $event->id,
                        'title' => $event->title,
                        'date' => $event->start_date,
                        'type' => $event->type ?? 'Not specified'
                    ],
                    'insights' => [
                        'id' => $insights->id,
                        'insight_type' => $insights->insight_type,
                        'satisfaction_score' => $insights->satisfaction_score,
                        'generated_at' => $insights->generated_at,
                        'analysis' => $analysisData // Complete analysis data
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
}
