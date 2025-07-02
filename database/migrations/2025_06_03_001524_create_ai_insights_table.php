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
        Schema::table('ai_insights', function (Blueprint $table) {
            // Add AI summary field
            $table->text('ai_summary')->nullable()->after('recommendations');
            
            // Add approval workflow fields
            $table->boolean('is_approved')->default(false)->after('ai_summary');
            $table->text('admin_notes')->nullable()->after('is_approved');
            $table->bigInteger('approved_by')->nullable()->after('admin_notes');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            
            // Add foreign key constraint
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            // Add indexes for better performance
            $table->index(['event_id', 'insight_type']);
            $table->index(['is_approved']);
            $table->index(['generated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_insights', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['approved_by']);
            
            // Drop indexes
            $table->dropIndex(['event_id', 'insight_type']);
            $table->dropIndex(['is_approved']);
            $table->dropIndex(['generated_at']);
            
            // Drop columns
            $table->dropColumn([
                'ai_summary',
                'is_approved', 
                'admin_notes',
                'approved_by',
                'approved_at'
            ]);
        });
    }
};