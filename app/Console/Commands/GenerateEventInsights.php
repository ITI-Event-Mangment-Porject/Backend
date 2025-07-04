<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIFeedbackAnalysisService;
use App\Models\Event\Event;
use App\Models\FeedbackAndAnalytics\AiInsight;
use Illuminate\Support\Facades\Log;

class GenerateEventInsights extends Command
{
    protected $signature = 'insights:generate 
                            {event_id? : The ID of the event to analyze}
                            {--completed-only : Only analyze completed events without existing insights}
                            {--all : Analyze all events with feedback}
                            {--regenerate : Force regenerate existing insights}';
                            
    protected $description = 'Generate AI insights for event feedback';

    public function handle(): int
    {
        try {
            // Check if OpenAI service is available
            if (!config('openai.api_key')) {
                $this->error('❌ OpenAI API key not configured. Please set OPENAI_API_KEY in your .env file');
                return self::FAILURE;
            }

            $eventId = $this->argument('event_id');
            $completedOnly = $this->option('completed-only');
            $all = $this->option('all');
            $regenerate = $this->option('regenerate');

            // Create service instance
            $aiService = app(AIFeedbackAnalysisService::class);

            if ($eventId) {
                return $this->analyzeSpecificEvent($aiService, (int) $eventId, $regenerate);
            } elseif ($completedOnly) {
                return $this->analyzeCompletedEvents($aiService);
            } elseif ($all) {
                return $this->analyzeAllEvents($aiService, $regenerate);
            } else {
                $this->showUsageHelp();
                return self::SUCCESS;
            }
        } catch (\Exception $e) {
            $this->error("❌ Command failed: {$e->getMessage()}");
            Log::error('AI Insights Command Error', [
                'command' => $this->signature,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return self::FAILURE;
        }
    }

    private function showUsageHelp(): void
    {
        $this->info('🤖 AI Insights Generator');
        $this->newLine();
        $this->info('Usage examples:');
        $this->line('  php artisan insights:generate 1              # Analyze specific event');
        $this->line('  php artisan insights:generate --completed-only # Analyze completed events');
        $this->line('  php artisan insights:generate --all           # Analyze all events');
        $this->line('  php artisan insights:generate 1 --regenerate  # Force regenerate insights');
        $this->newLine();
        
        // Show available events
        $this->showAvailableEvents();
    }

    private function showAvailableEvents(): void
    {
        $events = Event::whereHas('feedbackResponses')
            ->with('feedbackResponses')
            ->get();

        if ($events->isEmpty()) {
            $this->warn('⚠️  No events with feedback found.');
            $this->line('Create some feedback responses first to test AI insights.');
            return;
        }

        $this->info('📋 Available events with feedback:');
        $this->table(
            ['ID', 'Title', 'Type', 'Status', 'Feedback Count', 'Has Insights'],
            $events->map(function ($event) {
                $hasInsights = AiInsight::where('event_id', $event->id)->exists();
                return [
                    $event->id,
                    substr($event->title, 0, 30) . (strlen($event->title) > 30 ? '...' : ''),
                    $event->type,
                    $event->status,
                    $event->feedbackResponses->count(),
                    $hasInsights ? '✅' : '❌'
                ];
            })->toArray()
        );
    }

    private function analyzeSpecificEvent(AIFeedbackAnalysisService $aiService, int $eventId, bool $regenerate): int
    {
        $this->info("🔍 Analyzing event ID: {$eventId}");
        
        try {
            if ($regenerate) {
                $this->warn('🔄 Regenerating existing insights...');
                $report = $aiService->regenerateInsights($eventId);
            } else {
                $report = $aiService->generateEventFeedbackReport($eventId);
            }
            
            $this->displayEventResults($report);
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Failed: {$e->getMessage()}");
            
            // Provide helpful suggestions
            if (str_contains($e->getMessage(), 'No feedback data available')) {
                $this->warn('💡 Suggestion: Make sure the event has feedback responses before generating insights.');
            } elseif (str_contains($e->getMessage(), 'already exist')) {
                $this->warn('💡 Suggestion: Use --regenerate flag to update existing insights.');
            }
            
            return self::FAILURE;
        }
    }

    private function analyzeCompletedEvents(AIFeedbackAnalysisService $aiService): int
    {
        $events = Event::where('status', 'completed')
            ->whereHas('feedbackResponses')
            ->whereDoesntHave('aiInsights', function ($query) {
                $query->where('insight_type', 'feedback_summary');
            })
            ->get();

        if ($events->isEmpty()) {
            $this->info('✅ No new completed events need analysis');
            return self::SUCCESS;
        }

        return $this->processEventsBatch($aiService, $events, 'completed events');
    }

    private function analyzeAllEvents(AIFeedbackAnalysisService $aiService, bool $regenerate): int
    {
        $query = Event::whereHas('feedbackResponses');
        
        if (!$regenerate) {
            $query->whereDoesntHave('aiInsights', function ($q) {
                $q->where('insight_type', 'feedback_summary');
            });
        }
        
        $events = $query->get();

        if ($events->isEmpty()) {
            $this->info('✅ No events need analysis');
            return self::SUCCESS;
        }

        return $this->processEventsBatch($aiService, $events, 'all events', $regenerate);
    }

    private function processEventsBatch($aiService, $events, string $batchType, bool $regenerate = false): int
    {
        $this->info("🚀 Processing {$events->count()} {$batchType}...");
        
        $progressBar = $this->output->createProgressBar($events->count());
        $progressBar->start();

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($events as $event) {
            try {
                if ($regenerate) {
                    $aiService->regenerateInsights($event->id);
                } else {
                    $aiService->generateEventFeedbackReport($event->id);
                }
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Event '{$event->title}': {$e->getMessage()}";
                
                Log::error("Failed to analyze event", [
                    'event_id' => $event->id,
                    'event_title' => $event->title,
                    'error' => $e->getMessage()
                ]);
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->displayBatchResults($results);
        
        return $results['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function displayEventResults(array $report): void
    {
        $analysis = $report['analysis'];
        
        $this->newLine();
        $this->info("✅ Event: {$report['event']['title']}");
        $this->info("📊 Responses: {$report['total_responses']}");
        $this->info("🆔 Insight ID: {$report['insight_id']}");
        $this->newLine();
        
        $this->line("📈 <fg=green>Satisfaction:</> {$analysis['overall_satisfaction']}");
        $this->line("💭 <fg=blue>Sentiment:</> {$analysis['sentiment_analysis']}");
        
        if (!empty($analysis['key_strengths'])) {
            $this->line("💪 <fg=green>Top Strength:</> " . $analysis['key_strengths'][0]);
        }
        
        if (!empty($analysis['areas_for_improvement'])) {
            $this->line("🎯 <fg=yellow>Improvement:</> " . $analysis['areas_for_improvement'][0]);
        }

        if (!empty($analysis['recommendations'])) {
            $this->newLine();
            $this->info("📋 Top Recommendations:");
            foreach (array_slice($analysis['recommendations'], 0, 2) as $rec) {
                $priority = $rec['priority'] ?? 'medium';
                $action = $rec['action'] ?? 'No action specified';
                $this->line("  • [{$priority}] {$action}");
            }
        }
    }

    private function displayBatchResults(array $results): void
    {
        $this->info("🎉 Batch processing completed!");
        $this->info("✅ Successful: {$results['success']}");
        
        if ($results['failed'] > 0) {
            $this->warn("❌ Failed: {$results['failed']}");
            
            if (!empty($results['errors'])) {
                $this->newLine();
                $this->warn("Errors:");
                foreach (array_slice($results['errors'], 0, 5) as $error) {
                    $this->line("  • {$error}");
                }
                
                if (count($results['errors']) > 5) {
                    $this->line("  • ... and " . (count($results['errors']) - 5) . " more (check logs)");
                }
            }
        }
    }
}