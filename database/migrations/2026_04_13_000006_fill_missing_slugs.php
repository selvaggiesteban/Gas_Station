<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Course;
use App\Models\Topic;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rellenar slugs de Cursos
        Course::whereNull('slug')->orWhere('slug', '')->get()->each(function ($course) {
            $baseSlug = Str::slug($course->title);
            $slug = $baseSlug;
            $count = 1;
            
            // Evitar duplicados si ya existe el slug
            while (Course::where('slug', $slug)->where('id', '!=', $course->id)->exists()) {
                $slug = $baseSlug . '-' . $count++;
            }
            
            $course->update(['slug' => $slug]);
        });

        // Rellenar slugs de Temas
        Topic::whereNull('slug')->orWhere('slug', '')->get()->each(function ($topic) {
            $baseSlug = Str::slug($topic->title);
            $slug = $baseSlug;
            $count = 1;
            
            while (Topic::where('slug', $slug)->where('id', '!=', $topic->id)->exists()) {
                $slug = $baseSlug . '-' . $count++;
            }
            
            $topic->update(['slug' => $slug]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No es necesario revertir ya que son datos generados
    }
};
