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
        // Add indexes only if they don't already exist
        Schema::table('events', function (Blueprint $table) {
            // Check if indexes exist before adding them
            $indexes = $this->getTableIndexes('events');
            
            if (!in_array('events_status_index', $indexes)) {
                $table->index('status');
            }
            
            if (!in_array('events_type_status_index', $indexes)) {
                $table->index(['type', 'status']);
            }
            
            if (!in_array('events_start_date_end_date_index', $indexes)) {
                $table->index(['start_date', 'end_date']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Only drop indexes if they exist
            $indexes = $this->getTableIndexes('events');
            
            if (in_array('events_status_index', $indexes)) {
                $table->dropIndex(['status']);
            }
            
            if (in_array('events_type_status_index', $indexes)) {
                $table->dropIndex(['type', 'status']);
            }
            
            if (in_array('events_start_date_end_date_index', $indexes)) {
                $table->dropIndex(['start_date', 'end_date']);
            }
        });
    }

    /**
     * Get all indexes for a table
     */
    private function getTableIndexes(string $tableName): array
    {
        $indexes = DB::select("SHOW INDEX FROM {$tableName}");
        return array_column($indexes, 'Key_name');
    }
};