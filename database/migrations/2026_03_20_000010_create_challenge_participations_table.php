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
        Schema::create('challenge_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained('weekly_challenges')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('current_progress')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Unique constraint: one participation per user per challenge
            $table->unique(['challenge_id', 'user_id']);

            // Indexes
            $table->index('user_id');
            $table->index('is_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challenge_participations');
    }
};
