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
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('plan_id')->constrained()->onDelete('set null');
            }
            if (Schema::hasColumn('subscriptions', 'plan_id')) {
                $table->unsignedBigInteger('plan_id')->nullable()->change();
            }
            if (Schema::hasColumn('subscriptions', 'payment_provider')) {
                $table->string('payment_provider')->default('stripe')->after('payment_id')->change();
            }
            if (!Schema::hasColumn('subscriptions', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')->nullable()->index()->after('payment_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'product_id')) {
                $table->dropForeign(['product_id']);
                $table->dropColumn('product_id');
            }
            if (Schema::hasColumn('subscriptions', 'stripe_subscription_id')) {
                $table->dropColumn('stripe_subscription_id');
            }
            if (Schema::hasColumn('subscriptions', 'plan_id')) {
                $table->unsignedBigInteger('plan_id')->nullable(false)->change();
            }
        });
    }
};
