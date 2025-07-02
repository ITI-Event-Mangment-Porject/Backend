<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\AiInsight;
use App\Models\FeedbackResponse;
use App\Models\EventRegistration;
use App\Services\AIInsightService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AIInsightsController extends Controller
{
    protected AIInsightService $aiInsightService;

    public function __construct(AIInsightService $aiInsightService)
    {
        $this->aiInsightService = $aiInsightService;
        $this->middleware('auth');
        $this->middleware('role:admin')->except(['show']);
    }

    /**
     * Generate AI insights for an event
     */
    public function generate(Request $request, Event $event): JsonResponse
    {
        $request->validate([
            'insight_types' => 'array',
            'insight_types.*' => 'in:feedback_summary,attendance_analysis,engagement_metrics',
            'force_regenerate' => 'boolean'
        ]);

        try {
            $insightTypes = $request->input('insight_types', [
                'feedback_summary',
                'attendance_analysis',
                'engagement_metrics'
            ]);

            $insights = [];
            foreach ($insightTypes as $type) {
                $insight = $this->aiInsightService->generateInsight($event, $type, $request->boolean('force_regenerate'));
                $insights[] = $insight;
            }

            return response()->json([
                'success' => true,
                'message' => 'AI insights generated successfully',
                'data' => $insights
            ]);

        } catch (\Exception $e) {
            Log::error('AI Insight Generation Failed', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate insights: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get AI insights for an event
     */
    public function index(Event $event): JsonResponse
    {
        $insights = AiInsight::where('event_id', $event->id)
            ->orderBy('generated_at', 'desc')
            ->get()
            ->groupBy('insight_type');

        return response()->json([
            'success' => true,
            'data' => $insights
        ]);
    }

    /**
     * Get specific AI insight
     */
    public function show(AiInsight $insight): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $insight
        ]);
    }

    /**
     * Update/approve AI insight
     */
    public function update(Request $request, AiInsight $insight): JsonResponse
    {
        $request->validate([
            'recommendations' => 'string|max:2000',
            'is_approved' => 'boolean',
            'admin_notes' => 'string|max:1000'
        ]);

        $insight->update([
            'recommendations' => $request->input('recommendations', $insight->recommendations),
            'is_approved' => $request->boolean('is_approved'),
            'admin_notes' => $request->input('admin_notes'),
            'approved_by' => auth()->id(),
            'approved_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Insight updated successfully',
            'data' => $insight->fresh()
        ]);
    }

    /**
     * Get insights summary for dashboard
     */
    public function dashboard(): JsonResponse
    {
        $recentInsights = AiInsight::with('event')
            ->where('generated_at', '>=', now()->subDays(7))
            ->orderBy('generated_at', 'desc')
            ->limit(10)
            ->get();

        $stats = [
            'total_insights' => AiInsight::count(),
            'this_week' => AiInsight::where('generated_at', '>=', now()->subDays(7))->count(),
            'average_satisfaction' => AiInsight::avg('satisfaction_score'),
            'pending_approval' => AiInsight::whereNull('approved_at')->count()
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'recent_insights' => $recentInsights,
                'statistics' => $stats
            ]
        ]);
    }
}