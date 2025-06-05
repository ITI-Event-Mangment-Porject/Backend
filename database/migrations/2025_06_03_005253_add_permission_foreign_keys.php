<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration should run AFTER tracks, events, and users tables are created.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        // Add foreign key constraints to model_has_permissions
        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) {
            // Add foreign key constraints that were skipped in the initial migration
            $table->foreign('track_id', 'model_has_permissions_track_foreign')
                ->references('id')
                ->on('tracks')
                ->onDelete('cascade');
                
            $table->foreign('event_id', 'model_has_permissions_event_foreign')
                ->references('id')
                ->on('events')
                ->onDelete('cascade');
                
            $table->foreign('granted_by', 'model_has_permissions_granted_by_foreign')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // Add foreign key constraints to model_has_roles
        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) {
            $table->foreign('track_id', 'model_has_roles_track_foreign')
                ->references('id')
                ->on('tracks')
                ->onDelete('cascade');
                
            $table->foreign('event_id', 'model_has_roles_event_foreign')
                ->references('id')
                ->on('events')
                ->onDelete('cascade');
                
            $table->foreign('assigned_by', 'model_has_roles_assigned_by_foreign')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // Add foreign key constraints to role_has_permissions
        Schema::table($tableNames['role_has_permissions'], function (Blueprint $table) {
            $table->foreign('track_id', 'role_has_permissions_track_foreign')
                ->references('id')
                ->on('tracks')
                ->onDelete('cascade');
                
            $table->foreign('event_id', 'role_has_permissions_event_foreign')
                ->references('id')
                ->on('events')
                ->onDelete('cascade');
        });

        // Create additional ITIVENT-specific permission tables
        
        // Track-based role assignments (for users who are track coordinators, etc.)
        Schema::create('track_role_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('track_id');
            $table->string('role_type'); // 'coordinator', 'assistant', 'mentor'
            $table->unsignedBigInteger('assigned_by');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('track_id')->references('id')->on('tracks')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('restrict');

            $table->unique(['user_id', 'track_id', 'role_type'], 'unique_user_track_role');
            $table->index(['track_id', 'role_type']);
            $table->index('is_active');
        });

        // Event-specific permissions (beyond role assignments)
        Schema::create('event_specific_permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('event_id');
            $table->string('permission_type'); // 'check_in', 'manage_queue', 'view_analytics', etc.
            $table->unsignedBigInteger('granted_by');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('users')->onDelete('restrict');

            $table->unique(['user_id', 'event_id', 'permission_type'], 'unique_user_event_permission');
            $table->index(['event_id', 'permission_type']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        // Drop ITIVENT-specific tables first
        Schema::dropIfExists('event_specific_permissions');
        Schema::dropIfExists('track_role_assignments');

        // Remove foreign key constraints from permission tables
        Schema::table($tableNames['role_has_permissions'], function (Blueprint $table) {
            $table->dropForeign('role_has_permissions_track_foreign');
            $table->dropForeign('role_has_permissions_event_foreign');
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) {
            $table->dropForeign('model_has_roles_track_foreign');
            $table->dropForeign('model_has_roles_event_foreign');
            $table->dropForeign('model_has_roles_assigned_by_foreign');
        });

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) {
            $table->dropForeign('model_has_permissions_track_foreign');
            $table->dropForeign('model_has_permissions_event_foreign');
            $table->dropForeign('model_has_permissions_granted_by_foreign');
        });
    }
};