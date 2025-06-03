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
        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('speaker_name')->nullable();
            $table->text('speaker_bio')->nullable();
            $table->string('speaker_image', 500)->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location')->nullable();
            $table->integer('session_order')->default(1);
            $table->boolean('is_break')->default(false);
            $table->timestamps();
            
            $table->index(['event_id', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_sessions');
    }
};
