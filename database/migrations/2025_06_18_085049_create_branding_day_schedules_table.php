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
        Schema::create('branding_day_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('participation_id');
            $table->date('branding_day_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('order')->nullable();
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('participation_id')->references('id')->on('job_fair_participations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branding_day_schedules');
    }
};
