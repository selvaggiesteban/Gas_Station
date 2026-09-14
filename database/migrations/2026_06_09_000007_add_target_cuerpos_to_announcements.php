<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('academy_announcements', 'target_cuerpos')) {
            Schema::table('academy_announcements', function (Blueprint $table) {
                $table->json('target_cuerpos')->nullable()->after('target_roles');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('academy_announcements', 'target_cuerpos')) {
            Schema::table('academy_announcements', function (Blueprint $table) {
                $table->dropColumn('target_cuerpos');
            });
        }
    }
};
