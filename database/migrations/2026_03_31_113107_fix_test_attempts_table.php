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
        Schema::table('test_attempts', function (Blueprint $table) {
            $table->integer('questions_count')->default(0)->after('test_id');
            $table->integer('correct_count')->default(0)->after('questions_count');
            $table->integer('incorrect_count')->default(0)->after('correct_count');
            $table->integer('blank_count')->default(0)->after('incorrect_count');
            $table->integer('time_spent_seconds')->nullable()->after('blank_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_attempts', function (Blueprint $table) {
            $table->dropColumn(['questions_count', 'correct_count', 'incorrect_count', 'blank_count', 'time_spent_seconds']);
        });
    }
};
