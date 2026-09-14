<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academy_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('type')->default('info'); // info, warning, urgent
            $table->foreignId('published_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('target_roles')->nullable(); // Array of role names
            $table->timestamps();

            $table->index(['is_active', 'published_at']);
            $table->index('type');
        });

        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_announcement_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['academy_announcement_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('academy_announcements');
    }
};
