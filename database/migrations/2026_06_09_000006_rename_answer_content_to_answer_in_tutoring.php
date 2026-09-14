<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutoring_questions', function (Blueprint $table) {
            if (Schema::hasColumn('tutoring_questions', 'answer_content')) {
                $table->renameColumn('answer_content', 'answer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tutoring_questions', function (Blueprint $table) {
            if (Schema::hasColumn('tutoring_questions', 'answer')) {
                $table->renameColumn('answer', 'answer_content');
            }
        });
    }
};
