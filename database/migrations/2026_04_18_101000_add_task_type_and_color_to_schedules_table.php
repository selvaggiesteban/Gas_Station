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
        Schema::table('schedules', function (Blueprint $table) {
            $table->enum('task_type', ['temario', 'test', 'supuesto', 'repaso'])->nullable()->after('is_completed');
            $table->string('color')->nullable()->after('task_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['task_type', 'color']);
        });
    }
};
