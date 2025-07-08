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
        Schema::create('interview_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('order_key');
            $table->enum('status', ['waiting', 'in_interview', 'completed', 'no_show', 'cancelled'])->default('waiting');
            $table->timestamp('interview_started_at')->nullable();
            $table->timestamp('interview_ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique('interview_request_id', 'unique_request_queue');
            $table->index(['company_id', 'order_key']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_queue');
    }
};
