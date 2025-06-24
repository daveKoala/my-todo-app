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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->string('color', 7)->default('#ffffff'); // Hex color code
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->integer('sort_order')->default(0); // For custom ordering
            $table->json('labels')->nullable(); // For future label/tag support
            $table->timestamps();
            $table->softDeletes(); // For trash functionality

            // Indexes for better performance
            $table->index(['user_id', 'is_archived', 'deleted_at']);
            $table->index(['user_id', 'is_pinned']);
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'updated_at']);

            // Full-text search index (MySQL)
            $table->fullText(['title', 'content']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};