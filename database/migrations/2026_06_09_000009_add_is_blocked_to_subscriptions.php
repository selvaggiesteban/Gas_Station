<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('subscriptions', 'is_blocked')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->boolean('is_blocked')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscriptions', 'is_blocked')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('is_blocked');
            });
        }
    }
};
