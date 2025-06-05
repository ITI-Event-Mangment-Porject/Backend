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
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_name');
            $table->string('file_path', 500);
            $table->bigInteger('file_size');
            $table->string('mime_type', 100);
            $table->enum('file_type', ['image', 'document', 'video', 'audio', 'other']);
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('restrict');
            $table->string('related_type', 50)->nullable(); // events, companies, users, etc.
            $table->unsignedBigInteger('related_id')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamp('uploaded_at')->useCurrent();
            
            $table->index(['related_type', 'related_id']);
            $table->index('file_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
