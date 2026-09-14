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
        Schema::table('topics', function (Blueprint $table) {
            if (!Schema::hasColumn('topics', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_free_preview');
            }
            if (!Schema::hasColumn('topics', 'estimated_minutes')) {
                $table->integer('estimated_minutes')->default(30)->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'estimated_minutes']);
        });
    }
};
