<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->enum('insight_type', [
                'feedback_summary', 
                'attendance_analysis', 
                'engagement_metrics',
                'trend_analysis'
            ])->default('feedback_summary');
            $table->json('data'); // Store AI-generated insights
            $table->decimal('satisfaction_score', 3, 2)->nullable(); // Average satisfaction (0.00 to 5.00)
            $table->json('key_themes')->nullable(); // Common themes/keywords
            $table->text('recommendations')->nullable(); // AI recommendations summary
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['event_id', 'insight_type']);
            $table->index('generated_at');
            $table->index('satisfaction_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};