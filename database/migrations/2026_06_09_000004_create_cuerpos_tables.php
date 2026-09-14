<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuerpos', function (Blueprint $table) {
            $table->id();
            $table->string('name');          // 'Ayudantes IIPP'
            $table->string('slug')->unique(); // 'ayudantes-iipp'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Pivot: un usuario puede pertenecer a varios cuerpos (raro pero posible)
        Schema::create('cuerpo_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuerpo_id')->constrained('cuerpos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['cuerpo_id', 'user_id']);
        });

        // Tutor asignado (N12): un alumno con un tutor
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'assigned_teacher_id')) {
                $table->foreignId('assigned_teacher_id')->nullable()->after('id')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'assigned_teacher_id')) {
                $table->dropForeign(['assigned_teacher_id']);
                $table->dropColumn('assigned_teacher_id');
            }
        });
        Schema::dropIfExists('cuerpo_user');
        Schema::dropIfExists('cuerpos');
    }
};
