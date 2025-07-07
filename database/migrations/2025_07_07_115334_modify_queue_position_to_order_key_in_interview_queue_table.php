<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('interview_queue', function (Blueprint $table) {
            $table->renameColumn('queue_position', 'order_key');
        });

        // It's better to change the column type in a separate statement
        // for compatibility with more database versions.
        DB::statement('ALTER TABLE interview_queue MODIFY order_key DOUBLE DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interview_queue', function (Blueprint $table) {
            $table->renameColumn('order_key', 'queue_position');
        });

        // Revert the column type change as well
        DB::statement('ALTER TABLE interview_queue MODIFY queue_position INTEGER');
    }
};
