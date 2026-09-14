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
        Schema::create('battles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenger_id')->constrained('users');
            $table->foreignId('opponent_id')->constrained('users');
            $table->foreignId('topic_id')->constrained('topics');
            $table->enum('status', ['pending', 'accepted', 'declined', 'completed'])->default('pending');
            $table->integer('challenger_score')->nullable();
            $table->integer('opponent_score')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('battles');
    }
};
