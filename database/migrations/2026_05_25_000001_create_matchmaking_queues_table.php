<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('matchmaking_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('level');
            $table->unsignedBigInteger('topic_id')->nullable();
            $table->timestamp('queued_at');
            $table->timestamps();

            $table->unique(['user_id', 'level', 'topic_id'], 'user_queue_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('matchmaking_queues');
    }
};
