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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['Job Fair', 'Tech', 'Fun']);
            $table->enum('status', ['draft', 'published', 'ongoing', 'completed', 'archived'])->default('draft');
            $table->string('location')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('banner_image', 500)->nullable();
            $table->datetime('registration_deadline')->nullable();
            $table->enum('visibility_type', ['all', 'role_based', 'track_based'])->default('all');
            $table->json('visibility_config')->nullable();
            $table->string('slido_qr_code', 500)->nullable();
            $table->string('slido_embed_url', 500)->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            
            $table->index('type');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
