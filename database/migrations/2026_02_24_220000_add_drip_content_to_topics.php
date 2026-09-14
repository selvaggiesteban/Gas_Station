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
            $table->timestamp('available_at')->nullable()->after('is_free_preview');
            $table->index(['block_id', 'available_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex(['block_id', 'available_at']);
            $table->dropColumn('available_at');
        });
    }
};
