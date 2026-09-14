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
        Schema::create('zoom_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->string('meeting_id', 50);
            $table->text('join_url');
            $table->text('start_url')->nullable(); // Only for host
            $table->dateTime('start_time');
            $table->integer('duration_minutes')->default(60);
            $table->string('timezone', 50)->default('Europe/Madrid');
            $table->string('password', 20)->nullable();
            $table->json('settings')->nullable();
            $table->enum('status', ['scheduled', 'live', 'finished', 'cancelled'])->default('scheduled');
            $table->text('recording_url')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            // Indexes
            $table->index('meeting_id');
            $table->index('start_time');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_meetings');
    }
};
