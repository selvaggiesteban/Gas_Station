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
        Schema::create('topic_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('file_path');
            $table->enum('resource_type', ['summary', 'diagram', 'mind_map', 'extra']);
            $table->integer('file_size')->nullable(); // in bytes
            $table->integer('download_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('topic_id');
            $table->index('resource_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic_resources');
    }
};
