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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'stripe_product_id')) {
                $table->string('stripe_product_id')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('products', 'stripe_price_id')) {
                $table->string('stripe_price_id')->nullable()->after('stripe_product_id');
            }
            if (!Schema::hasColumn('products', 'is_recurring')) {
                $table->boolean('is_recurring')->default(true)->after('duration_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columnsToDrop = ['stripe_product_id', 'stripe_price_id', 'is_recurring'];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
