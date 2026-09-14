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
        Schema::table('questions', function (Blueprint $table) {
            $table->enum('type', ['multiple_choice', 'practical_case'])->default('multiple_choice')->after('test_id');
            $table->integer('max_score')->default(10)->after('explanation');
            $table->text('model_answer')->nullable()->after('max_score'); // For practical cases
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['type', 'max_score', 'model_answer']);
        });
    }
};
