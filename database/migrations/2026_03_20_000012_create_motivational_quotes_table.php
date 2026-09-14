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
        Schema::create('motivational_quotes', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->string('author')->nullable();
            $table->enum('category', ['study', 'battle', 'general'])->default('general');
            $table->boolean('is_active')->default(true);
            $table->integer('display_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motivational_quotes');
    }
};
