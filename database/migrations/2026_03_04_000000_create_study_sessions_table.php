<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('pomodoro'); // pomodoro, short_break, long_break
            $table->integer('duration_minutes')->default(25); // 25, 5, 15
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('completed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'started_at']);
            $table->index(['user_id', 'completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_sessions');
    }
};
