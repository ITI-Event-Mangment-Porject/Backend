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
        Schema::table('interview_queue', function (Blueprint $table) {
            $table->enum('status', ['waiting', 'in_interview', 'completed', 'skipped', 'cancelled', 'pending'])->default('waiting')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interview_queue', function (Blueprint $table) {
            $table->enum('status', ['waiting', 'in_interview', 'completed', 'skipped', 'cancelled', 'pending'])->default('waiting')->change();
        });
    }
};
