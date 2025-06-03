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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['registration', 'interview_update', 'queue_status', 'feedback_reminder', 'general', 'system']);
            $table->unsignedBigInteger('related_id')->nullable(); // Reference to related entity
            $table->string('related_type', 50)->nullable(); // Type of related entity
            $table->boolean('is_read')->default(false);
            $table->json('sent_via')->nullable(); // Channels: in-app, email
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
