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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('name'); // "Mensual", "Trimestral", "Anual"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['monthly', 'quarterly', 'yearly', 'pack']);
            $table->decimal('price', 10, 2);
            $table->integer('duration_days'); // 30, 90, 365
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable(); // ["acceso_completo", "certificados"]
            $table->integer('display_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('course_id');
            $table->index('type');
            $table->index('is_active');
            $table->index(['course_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
