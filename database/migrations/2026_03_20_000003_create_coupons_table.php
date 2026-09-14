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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->enum('type', ['percentage', 'fixed_amount']);
            $table->decimal('value', 10, 2)->comment('20.00 = 20% or 20€');
            $table->unsignedInteger('max_uses')->nullable()->comment('NULL = unlimited');
            $table->unsignedInteger('used_count')->default(0);
            $table->decimal('min_order_amount', 10, 2)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('applies_to', ['all', 'specific_plan'])->default('all');
            $table->foreignId('plan_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index('expires_at');
        });

        // Add coupon reference to subscriptions
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('plan_id')->constrained()->onDelete('set null');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('coupon_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['coupon_id', 'discount_amount']);
        });

        Schema::dropIfExists('coupons');
    }
};
