<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event\Event;
use App\Services\AIFeedbackAnalysisService;

class GenerateEventInsights extends Command
{
    protected $signature = 'insights:generate 
                          {event_id? : The ID of the event to analyze}
                          {--regenerate : Regenerate insights even if they already exist}
                          {--completed-only : Only analyze completed events}
                          {--all : Analyze all events with feedback}';

    protected $description = 'Generate AI insights for event feedback using Groq AI';

    private AIFeedbackAnalysisService $aiService;

    public function __construct(AIFeedbackAnalysisService $aiService)
    {
        parent::__construct();
        $this->aiService = $aiService;
    }

    public function handle()
    {
        // Check if Groq API key is configured
        if (!env('GROQ_API_KEY')) {
            $this->error('❌ Groq API key not configured!');
            $this->info('💡 Please add GROQ_API_KEY to your .env file');
            $this->info('🔗 Get your free API key at: https://console.groq.com/');
            return 1;
        }

        $eventId = $this->argument('event_id');
        $regenerate = $this->option('regenerate');
        $completedOnly = $this->option('completed-only');
        $analyzeAll = $this->option('all');

        if ($analyzeAll) {
            return $this->analyzeAllEvents($regenerate, $completedOnly);
        }

        if (!$eventId) {
            $this->error('❌ Please provide an event ID or use --all flag');
            $this->info('💡 Usage: php artisan insights:generate {event_id} [--regenerate]');
            $this->info('💡 Or: php artisan insights:generate --all [--completed-only]');
            return 1;
        }

        return $this->analyzeSingleEvent($eventId, $regenerate);
    }

    private function analyzeSingleEvent($eventId, $regenerate): int
    {
        try {
            $event = Event::findOrFail($eventId);
            
            $this->info("🔍 Analyzing event ID: {$eventId}");
            
            if ($regenerate) {
                $this->info("🔄 Regenerating existing insights...");
            }
            
            $result = $this->aiService->generateInsights($event, $regenerate);
            
            $this->info("✅ Success: {$result['message']}");
            $this->info("📊 Feedback responses analyzed: {$result['feedback_count']}");
            $this->info("🆔 Insight ID: {$result['insight_id']}");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Failed: {$e->getMessage()}");
            
            if (str_contains($e->getMessage(), 'already exist')) {
                $this->info("💡 Suggestion: Use --regenerate flag to update existing insights.");
            } elseif (str_contains($e->getMessage(), 'No feedback data')) {
                $this->info("💡 Suggestion: Make sure the event has feedback responses before generating insights.");
            } elseif (str_contains($e->getMessage(), 'API')) {
                $this->info("💡 Suggestion: Check your Groq API key and internet connection.");
            }
            
            return 1;
        }
    }

    private function analyzeAllEvents($regenerate, $completedOnly): int
    {
        $query = Event::whereHas('feedbackResponses');
        
        if ($completedOnly) {
            $query->where('end_date', '<', now());
        }
        
        $events = $query->get();
        
        if ($events->isEmpty()) {
            $this->warn('⚠️  No events found with feedback responses');
            return 1;
        }
        
        $this->info("🔍 Found {$events->count()} events with feedback");
        
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($events as $event) {
            $this->info("📝 Processing: {$event->title} (ID: {$event->id})");
            
            try {
                $result = $this->aiService->generateInsights($event, $regenerate);
                $this->info("  ✅ Success - {$result['feedback_count']} responses analyzed");
                $successCount++;
                
            } catch (\Exception $e) {
                $this->error("  ❌ Failed: {$e->getMessage()}");
                $failureCount++;
            }
        }
        
        $this->info("\n📊 Summary:");
        $this->info("✅ Successful: {$successCount}");
        $this->info("❌ Failed: {$failureCount}");
        
        return $failureCount > 0 ? 1 : 0;
    }
}
