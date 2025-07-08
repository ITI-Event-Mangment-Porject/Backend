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
        Schema::create('branding_day_speakers', function (Blueprint $table) {
            $table->id();
            $table->string('speaker_name');
            $table->string('position')->nullable();
            $table->string('mobile')->nullable();
            $table->string('photo')->nullable(); // Stores the file path
            $table->foreignId('job_fair_participation_id')->constrained('job_fair_participations')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branding_day_speakers');
    }
};
