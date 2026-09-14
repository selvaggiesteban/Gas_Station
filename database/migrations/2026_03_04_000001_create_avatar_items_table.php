<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avatar_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('type'); // uniform, badge, frame, accessory
            $table->string('image_path');
            $table->integer('unlock_level')->default(1); // Level required to unlock
            $table->string('unlock_rank')->nullable(); // Cobre, Bronce, Plata, Oro, Diamante
            $table->integer('unlock_battles_won')->nullable(); // Battles needed
            $table->integer('unlock_pomodoros')->nullable(); // Pomodoros needed
            $table->boolean('is_default')->default(false);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['type', 'unlock_level']);
        });

        // Pivot table for user unlocked items
        Schema::create('avatar_item_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('avatar_item_id')->constrained()->onDelete('cascade');
            $table->boolean('is_equipped')->default(false);
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->unique(['user_id', 'avatar_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avatar_item_user');
        Schema::dropIfExists('avatar_items');
    }
};
