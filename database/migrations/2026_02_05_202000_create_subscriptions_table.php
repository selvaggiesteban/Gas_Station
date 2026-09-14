<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->decimal('price', 8, 2);
                $table->integer('duration_in_days');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->string('status')->default('pending');
                $table->string('payment_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};