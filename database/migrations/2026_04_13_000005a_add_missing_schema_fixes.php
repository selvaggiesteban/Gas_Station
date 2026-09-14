<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Añadir slug a topics
        Schema::table('topics', function (Blueprint $table) {
            if (!Schema::hasColumn('topics', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('title');
            }
        });

        // 2. Corregir ENUM de niveles de usuario (Español y 5 niveles)
        // PASO A: Convertimos a VARCHAR para permitir cualquier texto temporalmente
        DB::statement("ALTER TABLE users MODIFY COLUMN level VARCHAR(255)");

        // PASO B: Traducimos los datos ahora que la columna es flexible
        DB::table('users')->where('level', 'Bronze')->update(['level' => 'Bronce']);
        DB::table('users')->where('level', 'Silver')->update(['level' => 'Plata']);
        DB::table('users')->where('level', 'Gold')->update(['level' => 'Oro']);
        
        // Normalizamos cualquier valor huérfano
        DB::statement("UPDATE users SET level = 'Cobre' WHERE level NOT IN ('Cobre', 'Bronce', 'Plata', 'Oro', 'Diamante') OR level IS NULL");

        // PASO C: Aplicamos el nuevo ENUM definitivo
        DB::statement("ALTER TABLE users MODIFY COLUMN level ENUM('Cobre', 'Bronce', 'Plata', 'Oro', 'Diamante') NOT NULL DEFAULT 'Cobre'");

        // 3. Corregir ENUM de tipos de test (Mismo procedimiento de seguridad)
        DB::statement("ALTER TABLE tests MODIFY COLUMN type VARCHAR(255)");
        
        DB::table('tests')->where('type', 'quiz')->update(['type' => 'test']);
        DB::table('tests')->where('type', 'mock_exam')->update(['type' => 'simulacro']);
        
        DB::statement("ALTER TABLE tests MODIFY COLUMN type ENUM('test', 'simulacro', 'practical_case') NOT NULL DEFAULT 'test'");
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN level ENUM('Cobre', 'Bronce', 'Plata', 'Oro', 'Diamante') DEFAULT 'Cobre'");
        DB::statement("ALTER TABLE tests MODIFY COLUMN type ENUM('test', 'simulacro', 'practical_case') DEFAULT 'test'");
    }
};
