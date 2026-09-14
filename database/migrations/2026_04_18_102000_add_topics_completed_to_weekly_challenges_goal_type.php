<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'topics_completed' to goal_type enum
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE weekly_challenges MODIFY COLUMN goal_type ENUM('points', 'pomodoros', 'tests_completed', 'battles_won', 'topics_completed') NOT NULL");
        } else {
            // For SQLite or others, we can't easily modify enum, so we leave it as is or change to string
            // but usually this project runs on MySQL
            Schema::table('weekly_challenges', function (Blueprint $table) {
                $table->string('goal_type')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE weekly_challenges MODIFY COLUMN goal_type ENUM('points', 'pomodoros', 'tests_completed', 'battles_won') NOT NULL");
        } else {
            Schema::table('weekly_challenges', function (Blueprint $table) {
                $table->string('goal_type')->change();
            });
        }
    }
};
