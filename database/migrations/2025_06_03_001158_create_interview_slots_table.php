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
        Schema::create('interview_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participation_id')->constrained('job_fair_participations')->onDelete('cascade');
            $table->date('slot_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes')->default(15);
            $table->integer('max_interviews_per_slot')->default(1);
            $table->boolean('is_break')->default(false);
            $table->string('break_reason')->nullable(); // lunch, prayer, etc.
            $table->boolean('is_available')->default(true);
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['participation_id', 'slot_date', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_slots');
    }
};
