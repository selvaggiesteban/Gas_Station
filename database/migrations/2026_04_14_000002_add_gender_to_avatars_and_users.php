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
        Schema::table('avatar_items', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('type')->comment('male, female, neutral');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('gender')->default('neutral')->after('avatar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avatar_items', function (Blueprint $table) {
            $table->dropColumn('gender');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
