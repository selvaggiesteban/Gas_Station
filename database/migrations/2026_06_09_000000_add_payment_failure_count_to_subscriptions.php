<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('subscriptions', 'payment_failure_count')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->unsignedTinyInteger('payment_failure_count')
                    ->default(0)
                    ->after('payment_token');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscriptions', 'payment_failure_count')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('payment_failure_count');
            });
        }
    }
};
