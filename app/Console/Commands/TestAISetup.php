<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event\Event;
use App\Models\FeedbackAndAnalytics\FeedbackResponse;
use App\Models\FeedbackAndAnalytics\AiInsight;

class TestAISetup extends Command
{
    protected $signature = 'ai:test-setup';
    protected $description = 'Test AI insights setup and show system status';

    public function handle(): int
    {
        $this->info('🤖 AI Insights Setup Test');
        $this->newLine();

        // Check OpenAI configuration
        $this->checkOpenAIConfig();
        
        // Check database tables
        $this->checkDatabaseTables();
        
        // Check sample data
        $this->checkSampleData();
        
        // Show next steps
        $this->showNextSteps();

        return self::SUCCESS;
    }

    private function checkOpenAIConfig(): void
    {
        $this->info('1. Checking OpenAI Configuration...');
        
        $apiKey = config('openai.api_key') ?? env('OPENAI_API_KEY');
        
        if ($apiKey) {
            $maskedKey = substr($apiKey, 0, 7) . '...' . substr($apiKey, -4);
            $this->line("   ✅ API Key configured: {$maskedKey}");
        } else {
            $this->line("   ❌ API Key not found");
            $this->warn("   💡 Add OPENAI_API_KEY=your_key_here to your .env file");
        }
        
        $this->newLine();
    }

    private function checkDatabaseTables(): void
    {
        $this->info('2. Checking Database Tables...');
        
        $tables = [
            'events' => Event::class,
            'feedback_responses' => FeedbackResponse::class,
            'ai_insights' => AiInsight::class,
        ];

        foreach ($tables as $tableName => $model) {
            try {
                $count = $model::count();
                $this->line("   ✅ {$tableName}: {$count} records");
            } catch (\Exception $e) {
                $this->line("   ❌ {$tableName}: Error - {$e->getMessage()}");
            }
        }
        
        $this->newLine();
    }

    private function checkSampleData(): void
    {
        $this->info('3. Checking Sample Data...');
        
        $eventsWithFeedback = Event::whereHas('feedbackResponses')->count();
        $totalFeedback = FeedbackResponse::count();
        $existingInsights = AiInsight::count();
        
        $this->line("   📊 Events with feedback: {$eventsWithFeedback}");
        $this->line("   💬 Total feedback responses: {$totalFeedback}");
        $this->line("   🤖 Existing AI insights: {$existingInsights}");
        
        if ($eventsWithFeedback === 0) {
            $this->warn("   ⚠️  No events with feedback found");
            $this->line("   💡 Create some test feedback to try AI insights");
        }
        
        $this->newLine();
    }

    private function showNextSteps(): void
    {
        $this->info('4. Next Steps:');
        
        $eventsWithFeedback = Event::whereHas('feedbackResponses')->with('feedbackResponses')->get();
        
        if ($eventsWithFeedback->isNotEmpty()) {
            $this->line('   🚀 Ready to test! Try these commands:');
            
            foreach ($eventsWithFeedback->take(3) as $event) {
                $feedbackCount = $event->feedbackResponses->count();
                $this->line("   php artisan insights:generate {$event->id}  # {$event->title} ({$feedbackCount} feedback)");
            }
        } else {
            $this->line('   📝 Create test data first:');
            $this->line('   php artisan db:seed --class=FeedbackResponseSeeder');
            $this->line('   php artisan insights:generate 1');
        }
        
        $this->newLine();
        $this->line('   📚 API endpoints:');
        $this->line('   POST /api/ai-insights/events/{id}/generate');
        $this->line('   GET  /api/ai-insights/events/{id}');
        $this->line('   GET  /api/ai-insights');
    }
}