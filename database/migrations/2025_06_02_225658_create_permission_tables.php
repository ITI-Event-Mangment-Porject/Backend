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
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        throw_if(empty($tableNames), new Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.'));

        // Create permissions table
        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name'); // e.g., 'create-events', 'manage-companies', 'view-analytics'
            $table->string('guard_name');
            $table->string('category')->nullable(); // e.g., 'events', 'companies', 'users', 'system'
            $table->text('description')->nullable(); // Human-readable description
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
            $table->index('category');
        });

        // Create roles table - adapted for ITIVENT system
        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            $table->bigIncrements('id');
            
            // Team support (for multi-tenant if needed)
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            
            $table->string('name'); // e.g., 'admin', 'staff', 'student', 'company_representative'
            $table->string('guard_name');
            $table->text('description')->nullable();
            $table->boolean('is_system_role')->default(false); // Prevent deletion of core roles
            $table->integer('priority')->default(0); // Higher number = higher priority
            $table->timestamps();

            if ($teams) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
            
            $table->index('is_system_role');
            $table->index('priority');
        });

        // Create model_has_permissions table
        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            
            // Add track-specific permissions support
            $table->unsignedBigInteger('track_id')->nullable();
            $table->unsignedBigInteger('event_id')->nullable(); // Event-specific permissions
            $table->timestamp('expires_at')->nullable(); // Temporary permissions
            $table->unsignedBigInteger('granted_by')->nullable(); // Who granted this permission
            $table->timestamps();

            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->index('track_id', 'model_has_permissions_track_id_index');
            $table->index('event_id', 'model_has_permissions_event_id_index');
            $table->index('expires_at', 'model_has_permissions_expires_at_index');

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
                
            // Foreign keys for tracks and events will be added later via separate migration
            // to avoid dependency issues during initial setup

            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');

                $table->primary([
                    $columnNames['team_foreign_key'], 
                    $pivotPermission, 
                    $columnNames['model_morph_key'], 
                    'model_type'
                ], 'model_has_permissions_permission_model_type_primary');
            } else {
                // Create a unique index instead of primary key to allow multiple permissions with different contexts
                $table->unique([
                    $pivotPermission, 
                    $columnNames['model_morph_key'], 
                    'model_type',
                    'track_id',
                    'event_id'
                ], 'model_has_permissions_unique_context');
            }
        });

        // Create model_has_roles table
        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            
            // Add ITIVENT-specific context
            $table->unsignedBigInteger('track_id')->nullable();
            $table->unsignedBigInteger('event_id')->nullable(); // Event-specific roles (e.g., event staff)
            $table->timestamp('expires_at')->nullable(); // Temporary role assignments
            $table->unsignedBigInteger('assigned_by')->nullable(); // Who assigned this role
            $table->text('notes')->nullable(); // Assignment notes
            $table->timestamps();

            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->index('track_id', 'model_has_roles_track_id_index');
            $table->index('event_id', 'model_has_roles_event_id_index');
            $table->index('expires_at', 'model_has_roles_expires_at_index');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');
                
            // Foreign keys for tracks and events will be added later via separate migration
            // to avoid dependency issues during initial setup

            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');

                $table->primary([
                    $columnNames['team_foreign_key'], 
                    $pivotRole, 
                    $columnNames['model_morph_key'], 
                    'model_type'
                ], 'model_has_roles_role_model_type_primary');
            } else {
                // Create a unique index to allow multiple role assignments with different contexts
                $table->unique([
                    $pivotRole, 
                    $columnNames['model_morph_key'], 
                    'model_type',
                    'track_id',
                    'event_id'
                ], 'model_has_roles_unique_context');
            }
        });

        // Create role_has_permissions table
        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);
            
            // Add context-specific permissions for roles
            $table->unsignedBigInteger('track_id')->nullable(); // Track-specific role permissions
            $table->unsignedBigInteger('event_id')->nullable(); // Event-specific role permissions
            $table->timestamps();

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');
                
            // Foreign keys for tracks and events will be added later via separate migration
            // to avoid dependency issues during initial setup

            // Allow same role-permission combination for different contexts
            $table->unique([
                $pivotPermission, 
                $pivotRole, 
                'track_id', 
                'event_id'
            ], 'role_has_permissions_unique_context');
            
            $table->index('track_id');
            $table->index('event_id');
        });

        // NOTE: The ITIVENT-specific tables below should be created AFTER 
        // the tracks, events, and users tables exist. Consider moving these 
        // to a separate migration file that runs later.
        
        /*
        // Track-based role assignments (for users who are track coordinators, etc.)
        Schema::create('track_role_assignments', static function (Blueprint $table) {
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
        Schema::create('event_specific_permissions', static function (Blueprint $table) {
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
        */
        // Clear permission cache
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');
        }

        // Drop ITIVENT-specific tables first (if they exist)
        // Schema::dropIfExists('event_specific_permissions');
        // Schema::dropIfExists('track_role_assignments');

        // Drop core permission tables
        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};