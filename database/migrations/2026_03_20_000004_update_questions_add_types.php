<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the type column to include new question types
        DB::statement("ALTER TABLE questions MODIFY COLUMN type ENUM('multiple_choice', 'practical_case', 'five_options', 'fill_blank', 'open_ended') DEFAULT 'multiple_choice'");

        // Add new columns for additional question types
        Schema::table('questions', function (Blueprint $table) {
            $table->text('fill_blank_text')->nullable()->comment('For fill_blank type: stores the correct text');
            $table->integer('open_ended_max_words')->default(500)->comment('Max words for open_ended questions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['fill_blank_text', 'open_ended_max_words']);
        });

        // Revert type column
        DB::statement("ALTER TABLE questions MODIFY COLUMN type ENUM('multiple_choice', 'practical_case') DEFAULT 'multiple_choice'");
    }
};
