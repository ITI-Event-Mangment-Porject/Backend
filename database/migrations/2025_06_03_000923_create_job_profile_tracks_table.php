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
        Schema::create('job_profile_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_role_id')->constrained('job_profiles')->onDelete('cascade');
            $table->foreignId('track_id')->constrained()->onDelete('cascade');
            $table->enum('preference_level', ['required', 'preferred', 'acceptable'])->default('preferred');
            $table->timestamp('created_at')->useCurrent();
            
            $table->unique(['job_role_id', 'track_id'], 'unique_job_track');
            $table->index('job_role_id');
            $table->index('track_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_profile_tracks');
    }
};
