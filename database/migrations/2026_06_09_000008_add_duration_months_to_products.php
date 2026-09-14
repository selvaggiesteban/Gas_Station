<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'duration_months')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedTinyInteger('duration_months')->default(12)->after('price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'duration_months')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('duration_months');
            });
        }
    }
};
