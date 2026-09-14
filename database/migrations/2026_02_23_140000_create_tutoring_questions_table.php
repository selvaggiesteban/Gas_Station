<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutoring_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('topic_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->text('content');
            $table->string('status')->default('pending'); // pending, answered, resolved, escalated
            $table->foreignId('answered_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('answered_at')->nullable();
            $table->text('answer_content')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index('status');
            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutoring_questions');
    }
};
