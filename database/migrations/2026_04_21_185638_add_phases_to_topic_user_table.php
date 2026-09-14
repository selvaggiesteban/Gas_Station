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
        Schema::table('topic_user', function (Blueprint $table) {
            $table->boolean('phase_reading')->default(false)->after('is_completed');
            $table->boolean('phase_underlining')->default(false)->after('phase_reading');
            $table->boolean('phase_review')->default(false)->after('phase_underlining');
            $table->boolean('phase_fast_review')->default(false)->after('phase_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topic_user', function (Blueprint $table) {
            $table->dropColumn(['phase_reading', 'phase_underlining', 'phase_review', 'phase_fast_review']);
        });
    }
};
