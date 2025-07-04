<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Event\Event;
use App\Models\FeedbackAndAnalytics\FeedbackResponse;
use App\Models\FeedbackAndAnalytics\AiInsight;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AIFeedbackAnalysisService
{
    public function generateEventFeedbackReport(int $eventId): array
    {
        // Check if insights already exist
        $existingInsight = $this->getEventInsights($eventId);
        if ($existingInsight) {
            throw new \Exception('Insights already exist for this event. Use regenerate=true to update.');
        }

        return $this->performAnalysis($eventId);
    }

    public function regenerateInsights(int $eventId): array
    {
        // Delete existing insights
        AiInsight::where('event_id', $eventId)
            ->where('insight_type', 'feedback_summary')
            ->delete();

        // Clear cache
        Cache::forget("event_insights_{$eventId}");

        return $this->performAnalysis($eventId);
    }

    private function performAnalysis(int $eventId): array
    {
        $event = Event::findOrFail($eventId);
        $feedbackResponses = $this->getFeedbackData($eventId);
        
        if ($feedbackResponses->isEmpty()) {
            throw new \Exception("No feedback data available for event: {$event->title}");
        }

        // Prepare feedback data for AI analysis
        $feedbackText = $this->prepareFeedbackForAnalysis($feedbackResponses, $event);
        
        // Generate AI insights
        $aiAnalysis = $this->analyzeWithOpenAI($feedbackText, $event);
        
        // Save insights to database
        $insight = $this->saveInsights($eventId, $aiAnalysis);
        
        // Prepare result
        $result = [
            'event' => $event->only(['id', 'title', 'type', 'start_date', 'end_date', 'status']),
            'total_responses' => $feedbackResponses->count(),
            'analysis' => $aiAnalysis,
            'insight_id' => $insight->id,
            'generated_at' => $insight->generated_at
        ];

        // Cache the results
        Cache::put("event_insights_{$eventId}", $result, now()->addHours(24));
        
        Log::info("AI insights generated for event: {$event->title}", [
            'event_id' => $eventId,
            'responses_count' => $feedbackResponses->count(),
            'insight_id' => $insight->id
        ]);
        
        return $result;
    }

    private function getFeedbackData(int $eventId): Collection
    {
        return FeedbackResponse::where('event_id', $eventId)
            ->with(['user:id,first_name,last_name', 'form:id,title'])
            ->orderBy('submitted_at', 'desc')
            ->get();
    }

    private function prepareFeedbackForAnalysis(Collection $feedbackResponses, Event $event): string
    {
        $feedbackText = "ITI Event Feedback Analysis Data:\n\n";
        $feedbackText .= "Event: {$event->title}\n";
        $feedbackText .= "Type: {$event->type}\n";
        $feedbackText .= "Date: {$event->start_date} to {$event->end_date}\n";
        $feedbackText .= "Total Responses: {$feedbackResponses->count()}\n\n";
        
        // Add rating statistics
        $ratings = $feedbackResponses->whereNotNull('overall_rating')->pluck('overall_rating');
        if ($ratings->isNotEmpty()) {
            $feedbackText .= "Rating Statistics:\n";
            $feedbackText .= "Average Rating: " . round($ratings->avg(), 2) . "/5\n";
            $feedbackText .= "Rating Distribution: " . $ratings->countBy()->toJson() . "\n\n";
        }
        
        $feedbackText .= "Individual Responses:\n";
        $feedbackText .= str_repeat("=", 50) . "\n";
        
        foreach ($feedbackResponses as $index => $response) {
            $responses = is_array($response->responses) ? $response->responses : json_decode($response->responses, true);
            
            $feedbackText .= "Response #" . ($index + 1) . "\n";
            $feedbackText .= "User: {$response->user->first_name} {$response->user->last_name}\n";
            $feedbackText .= "Overall Rating: {$response->overall_rating}/5\n";
            $feedbackText .= "Submitted: {$response->submitted_at}\n";
            
            if (is_array($responses)) {
                foreach ($responses as $question => $answer) {
                    $feedbackText .= "Q: {$question}\n";
                    $feedbackText .= "A: {$answer}\n";
                }
            }
            $feedbackText .= str_repeat("-", 30) . "\n";
        }
        
        return $feedbackText;
    }

    private function analyzeWithOpenAI(string $feedbackText, Event $event): array
    {
        try {
            $prompt = $this->buildAnalysisPrompt($feedbackText, $event);
            
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert event analyst for ITI (Information Technology Institute) events. Analyze feedback and provide actionable insights for improving educational and professional development events. Focus on practical recommendations that event organizers can implement.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 3000,
                'temperature' => 0.2,
            ]);

            $aiResponse = $response->choices[0]->message->content;
            return $this->parseAIResponse($aiResponse);
            
        } catch (\Exception $e) {
            Log::error("OpenAI API error for event {$event->id}: " . $e->getMessage());
            throw new \Exception("Failed to analyze feedback with AI: " . $e->getMessage());
        }
    }

    private function buildAnalysisPrompt(string $feedbackText, Event $event): string
    {
        return $feedbackText . "\n\n" . 
        "Please analyze this ITI event feedback and provide comprehensive insights in JSON format:
        {
            \"overall_satisfaction\": \"score out of 100 with brief explanation\",
            \"key_strengths\": [\"top 3-5 strengths mentioned by attendees\"],
            \"areas_for_improvement\": [\"top 3-5 areas needing improvement\"],
            \"common_themes\": [\"recurring themes and topics in feedback\"],
            \"sentiment_analysis\": \"overall sentiment (positive/neutral/negative) with detailed reasoning\",
            \"attendance_insights\": \"patterns about attendance, engagement, and participation\",
            \"technical_feedback\": \"specific technical aspects, tools, or content mentioned\",
            \"recommendations\": [
                {\"priority\": \"high\", \"action\": \"specific actionable recommendation\", \"impact\": \"expected positive outcome\", \"implementation\": \"how to implement this\"},
                {\"priority\": \"medium\", \"action\": \"specific actionable recommendation\", \"impact\": \"expected positive outcome\", \"implementation\": \"how to implement this\"},
                {\"priority\": \"low\", \"action\": \"specific actionable recommendation\", \"impact\": \"expected positive outcome\", \"implementation\": \"how to implement this\"}
            ],
            \"summary\": \"executive summary for ITI administrators (2-3 sentences)\",
            \"event_specific_insights\": \"insights specific to this {$event->type} event type\",
            \"future_planning_suggestions\": \"suggestions for planning similar events in the future\"
        }
        
        Focus on actionable insights that ITI event organizers can use to improve future {$event->type} events. Consider the educational context and professional development goals of ITI.";
    }

    private function parseAIResponse(string $aiResponse): array
    {
        // Clean response and extract JSON
        $aiResponse = trim($aiResponse);
        $aiResponse = preg_replace('/```json\s*/', '', $aiResponse);
        $aiResponse = preg_replace('/```\s*$/', '', $aiResponse);
        
        $jsonStart = strpos($aiResponse, '{');
        $jsonEnd = strrpos($aiResponse, '}') + 1;
        
        if ($jsonStart !== false && $jsonEnd !== false) {
            $jsonString = substr($aiResponse, $jsonStart, $jsonEnd - $jsonStart);
            $parsed = json_decode($jsonString, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->validateAnalysisStructure($parsed);
            }
        }
        
        Log::warning("Failed to parse AI response", ['response' => substr($aiResponse, 0, 500)]);
        
        return $this->getFallbackAnalysis($aiResponse);
    }

    private function validateAnalysisStructure(array $analysis): array
    {
        $required = [
            'overall_satisfaction' => 'Not available',
            'key_strengths' => [],
            'areas_for_improvement' => [],
            'common_themes' => [],
            'sentiment_analysis' => 'Not available',
            'attendance_insights' => 'Not available',
            'technical_feedback' => 'Not available',
            'recommendations' => [],
            'summary' => 'Analysis completed successfully',
            'event_specific_insights' => 'Not available',
            'future_planning_suggestions' => 'Not available'
        ];

        return array_merge($required, $analysis);
    }

    private function getFallbackAnalysis(string $rawResponse): array
    {
        return [
            'overall_satisfaction' => 'Unable to parse response',
            'key_strengths' => ['Analysis completed but parsing failed'],
            'areas_for_improvement' => ['Review AI response format'],
            'common_themes' => ['Data processing issue'],
            'sentiment_analysis' => 'Unable to analyze due to parsing error',
            'attendance_insights' => 'Unable to generate',
            'technical_feedback' => 'Unable to extract',
            'recommendations' => [
                ['priority' => 'high', 'action' => 'Review AI analysis system', 'impact' => 'Improved insights', 'implementation' => 'Check logs and regenerate']
            ],
            'summary' => 'AI analysis completed but response format needs review. Please regenerate insights.',
            'event_specific_insights' => 'Unable to generate',
            'future_planning_suggestions' => 'Unable to generate'
        ];
    }

    private function saveInsights(int $eventId, array $analysis): AiInsight
    {
        return AiInsight::create([
            'event_id' => $eventId,
            'insight_type' => 'feedback_summary',
            'data' => json_encode($analysis),
            'satisfaction_score' => $this->extractSatisfactionScore($analysis),
            'key_themes' => json_encode($analysis['common_themes'] ?? []),
            'recommendations' => $analysis['summary'] ?? '',
            'generated_at' => now()
        ]);
    }

    private function extractSatisfactionScore(array $analysis): ?float
    {
        $satisfaction = $analysis['overall_satisfaction'] ?? '';
        
        if (preg_match('/(\d+(?:\.\d+)?)/', $satisfaction, $matches)) {
            $score = (float) $matches[1];
            return $score > 5 ? $score / 20 : $score; // Convert to 5-point scale
        }
        
        return null;
    }

    public function getEventInsights(int $eventId): ?AiInsight
    {
        return Cache::remember("event_insights_model_{$eventId}", 3600, function () use ($eventId) {
            return AiInsight::where('event_id', $eventId)
                ->where('insight_type', 'feedback_summary')
                ->latest('generated_at')
                ->first();
        });
    }
}