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
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable(); // Rich text description
            $table->string('video_url')->nullable(); // YouTube/Vimeo link
            $table->string('pdf_url')->nullable(); // Downloadable resource
            $table->integer('order')->default(0);
            $table->boolean('is_free_preview')->default(false); // For marketing
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
