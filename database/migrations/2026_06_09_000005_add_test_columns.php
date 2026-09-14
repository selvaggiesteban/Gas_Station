<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            if (!Schema::hasColumn('tests', 'duration_minutes')) {
                $table->unsignedInteger('duration_minutes')->nullable()->after('time_limit_minutes');
            }
            if (!Schema::hasColumn('tests', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('passing_score');
            }
            if (!Schema::hasColumn('tests', 'is_free')) {
                $table->boolean('is_free')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            if (Schema::hasColumn('tests', 'duration_minutes')) $table->dropColumn('duration_minutes');
            if (Schema::hasColumn('tests', 'is_active')) $table->dropColumn('is_active');
            if (Schema::hasColumn('tests', 'is_free')) $table->dropColumn('is_free');
        });
    }
};
