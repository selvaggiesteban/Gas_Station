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
            $table->integer('current_study_round')->default(1)->after('level');
        });

        Schema::table('topic_user', function (Blueprint $table) {
            $table->integer('round_number')->default(1)->after('topic_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('current_study_round');
        });

        Schema::table('topic_user', function (Blueprint $table) {
            $table->dropColumn('round_number');
        });
    }
};
