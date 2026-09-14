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
        Schema::create('weekly_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('goal_type', ['points', 'pomodoros', 'tests_completed', 'battles_won']);
            $table->integer('goal_amount');
            $table->integer('reward_points')->default(0);
            $table->foreignId('reward_badge_id')->nullable()->constrained('avatar_items')->onDelete('set null');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_challenges');
    }
};
