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
        Schema::create('custom_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->json('blocks')->nullable(); // IDs de bloques seleccionados
            $table->json('topics')->nullable(); // IDs de temas seleccionados
            $table->json('question_types')->nullable(); // ["multiple_choice", "fill_blank"]
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'mixed'])->default('mixed');
            $table->integer('question_count')->default(20);
            $table->integer('time_limit_minutes')->default(30);
            $table->enum('status', ['draft', 'generated', 'completed'])->default('draft');
            $table->json('generated_questions')->nullable(); // IDs de preguntas seleccionadas
            $table->integer('score')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_tests');
    }
};
