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
        Schema::table('branding_day_schedules', function (Blueprint $table) {
            $table->foreignId('branding_day_speaker_id')->nullable()->constrained('branding_day_speakers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branding_day_schedules', function (Blueprint $table) {
            $table->dropForeign(['branding_day_speaker_id']);
            $table->dropColumn('branding_day_speaker_id');
        });
    }
};
