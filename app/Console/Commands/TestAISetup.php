<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Event\Event;
use App\Models\FeedbackAndAnalytics\FeedbackResponse;

class TestAISetup extends Command
{
    protected $signature = 'ai:test-setup';
    protected $description = 'Test AI setup and configuration for Groq';

    public function handle()
    {
        $this->info('🧪 Testing AI Setup for ITIVENT...');
        $this->newLine();

        // Test 1: Check Groq API Key
        $this->info('1️⃣ Checking Groq API Key...');
        if (!env('GROQ_API_KEY')) {
            $this->error('   ❌ GROQ_API_KEY not found in .env file');
            $this->info('   💡 Get your free API key at: https://console.groq.com/');
            return 1;
        }
        $this->info('   ✅ Groq API key is configured');

        // Test 2: Test Groq API Connection
        $this->info('2️⃣ Testing Groq API Connection...');
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama3-8b-8192',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Hello, this is a test message. Please respond with "API connection successful".'
                    ]
                ],
                'max_tokens' => 50,
            ]);

            if ($response->successful()) {
                $this->info('   ✅ Groq API connection successful');
                $responseData = $response->json();
                if (isset($responseData['choices'][0]['message']['content'])) {
                    $this->info('   📝 Response: ' . trim($responseData['choices'][0]['message']['content']));
                }
            } else {
                $this->error('   ❌ Groq API connection failed: ' . $response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Groq API test failed: ' . $e->getMessage());
            return 1;
        }

        // Test 3: Check Database Tables
        $this->info('3️⃣ Checking Database Tables...');
        try {
            $eventCount = Event::count();
            $feedbackCount = FeedbackResponse::count();
            
            $this->info("   ✅ Events table: {$eventCount} records");
            $this->info("   ✅ Feedback responses table: {$feedbackCount} records");
            
            if ($feedbackCount === 0) {
                $this->warn('   ⚠️  No feedback responses found - you may need sample data');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Database check failed: ' . $e->getMessage());
            return 1;
        }

        // Test 4: Check Events with Feedback
        $this->info('4️⃣ Checking Events with Feedback...');
        $eventsWithFeedback = Event::whereHas('feedbackResponses')->get(['id', 'title']);
        
        if ($eventsWithFeedback->isEmpty()) {
            $this->warn('   ⚠️  No events found with feedback responses');
            $this->info('   💡 You may need to create sample feedback data for testing');
        } else {
            $this->info("   ✅ Found {$eventsWithFeedback->count()} events with feedback:");
            foreach ($eventsWithFeedback as $event) {
                $feedbackCount = $event->feedbackResponses()->count();
                $this->info("      • Event {$event->id}: {$event->title} ({$feedbackCount} responses)");
            }
        }

        $this->newLine();
        $this->info('🎉 AI Setup Test Complete!');
        
        if ($eventsWithFeedback->isNotEmpty()) {
            $firstEvent = $eventsWithFeedback->first();
            $this->info("💡 Try running: php artisan insights:generate {$firstEvent->id}");
        }

        return 0;
    }
}
