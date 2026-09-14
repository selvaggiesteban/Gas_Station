<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'topic_id')) {
                $table->foreignId('topic_id')->nullable()->after('user_id')
                    ->constrained('topics')->nullOnDelete();
            }
            if (!Schema::hasColumn('schedules', 'phase')) {
                $table->string('phase')->nullable()->after('topic_id');
            }
            if (!Schema::hasColumn('schedules', 'is_from_academy')) {
                $table->boolean('is_from_academy')->default(false)->after('is_official');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'topic_id')) {
                $table->dropForeign(['topic_id']);
                $table->dropColumn('topic_id');
            }
            if (Schema::hasColumn('schedules', 'phase')) {
                $table->dropColumn('phase');
            }
            if (Schema::hasColumn('schedules', 'is_from_academy')) {
                $table->dropColumn('is_from_academy');
            }
        });
    }
};
