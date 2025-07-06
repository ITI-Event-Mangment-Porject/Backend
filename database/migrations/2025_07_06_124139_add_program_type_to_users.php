<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'program')) {
                $table->enum('program', ['PTP', 'ITP'])->default('PTP')->after('graduation_year')
                    ->comment('PTP: Professional Training Program, ITP: Intensive Training Program');
            }

            if (!Schema::hasColumn('users', 'intake')) {
                $table->integer('intake')->nullable()->after('program')
                    ->comment('Intake number, e.g., 45, 46');
            }

            if (!Schema::hasColumn('users', 'round')) {
                $table->integer('round')->nullable()->after('intake')
                    ->comment('Round number, e.g., 1, 2, 3');
            }

            if (!Schema::hasColumn('users', 'intake_year')) {
                $table->integer('intake_year')->nullable()->after('round')
                    ->comment('Year of the intake, e.g., 2024, 2025');
            }
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
