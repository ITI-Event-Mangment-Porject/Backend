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
        Schema::create('job_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participation_id')->constrained('job_fair_participations')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->enum('employment_type', ['Full-time', 'Part-time', 'Internship', 'Contract']);
            $table->string('location')->nullable();
            $table->integer('positions_available')->default(1);
            $table->timestamps();
            
            $table->index('participation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_profiles');
    }
};
