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
        Schema::dropIfExists('user_activities');
        
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->string('url');
            $table->string('route_name')->nullable();
            $table->string('method', 10);
            $table->string('action_type', 50);
            $table->string('ip_address', 45);
            $table->json('device_info')->nullable();
            $table->integer('time_spent')->default(0);
            $table->timestamps();

            // Índices compuestos críticos
            $table->index(['user_id', 'created_at']);
            $table->index(['action_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
