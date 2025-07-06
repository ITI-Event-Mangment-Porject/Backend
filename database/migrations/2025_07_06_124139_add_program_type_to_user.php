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
        Schema::table('user', function (Blueprint $table) {
            //
            $table->enum('program', ['PTP', 'ITP'])->default('PTP')->after('graduation_year')
                ->comment('PTP: Professional Trainig Program, ITP: Intensive Trainig Program');
            $table->integer('intake')->nullable()->after('program')
                ->comment('Intake number, e.g., 45, 46');
            $table->integer('round')->nullable()->after('intake')
                ->comment('Round number, e.g., 1, 2, 3');
            $table->integer('intake_year')->nullable()->after('round')
                ->comment('Year of the intake, e.g., 2024, 2025');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            //
        });
    }
};
