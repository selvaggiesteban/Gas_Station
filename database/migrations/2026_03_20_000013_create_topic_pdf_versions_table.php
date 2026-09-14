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
        Schema::create('topic_pdf_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->integer('version_number');
            $table->string('file_path');
            $table->timestamp('upload_date')->useCurrent();
            $table->boolean('is_current')->default(false);
            $table->text('change_notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Unique constraint: one version number per topic
            $table->unique(['topic_id', 'version_number']);

            // Indexes
            $table->index('topic_id');
            $table->index('is_current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic_pdf_versions');
    }
};
