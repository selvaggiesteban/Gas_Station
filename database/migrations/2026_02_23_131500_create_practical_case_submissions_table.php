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
        Schema::create('practical_case_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('test_attempt_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->text('answer_text')->nullable();
            $table->json('attachments')->nullable(); // URLs to uploaded files
            $table->integer('auto_score')->nullable(); // Auto-score based on keywords
            $table->integer('teacher_score')->nullable(); // Manual review by teacher
            $table->text('teacher_feedback')->nullable();
            $table->enum('status', ['pending', 'auto_scored', 'reviewed', 'challenged'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['test_attempt_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practical_case_submissions');
    }
};
