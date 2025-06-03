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
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->enum('insight_type', ['feedback_summary', 'attendance_analysis', 'engagement_metrics']);
            $table->json('data'); // Store AI-generated insights
            $table->decimal('satisfaction_score', 3, 2)->nullable(); // Average satisfaction
            $table->json('key_themes')->nullable(); // Common themes/keywords
            $table->text('recommendations')->nullable();
            $table->timestamp('generated_at')->useCurrent();
            
            $table->index(['event_id', 'insight_type']);
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
