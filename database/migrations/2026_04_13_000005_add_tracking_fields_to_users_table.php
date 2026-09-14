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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable();
            $table->integer('login_streak_days')->default(0);
            $table->integer('total_study_minutes')->default(0);
            $table->integer('total_tests_completed')->default(0);
            $table->integer('total_classes_viewed')->default(0);
            $table->integer('total_supuestos_submitted')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_login_at', 
                'login_streak_days', 
                'total_study_minutes',
                'total_tests_completed',
                'total_classes_viewed',
                'total_supuestos_submitted'
            ]);
        });
    }
};
