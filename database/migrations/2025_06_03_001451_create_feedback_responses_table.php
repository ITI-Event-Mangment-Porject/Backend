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
        Schema::create('feedback_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('feedback_forms')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->json('responses'); // Store all form responses
            $table->integer('overall_rating')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            
            $table->unique(['user_id', 'form_id'], 'unique_user_form');
            $table->index(['event_id', 'overall_rating']);
            
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_responses');
    }
};
