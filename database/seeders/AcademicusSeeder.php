<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Block;
use App\Models\Topic;
use App\Models\Test;
use App\Models\Question;
use App\Models\Option;
use App\Models\Plan;
use Illuminate\Support\Str;

class AcademicusSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            ['title' => 'Ayudantes de IIPP', 'description' => 'Curso integral.'],
            ['title' => 'Curso de técnicas de estudio', 'description' => 'Optimiza tu tiempo.'],
        ];

        foreach ($courses as $c) {
            Course::updateOrCreate(
                ['title' => $c['title']],
                [
                    'slug' => Str::slug($c['title']),
                    'description' => $c['description'],
                    'price' => 99.99,
                    'is_active' => true,
                ]
            );
        }

        $mainCourse = Course::where('title', 'Ayudantes de IIPP')->first();
        if (!$mainCourse) return;

        $block1 = Block::updateOrCreate(['course_id' => $mainCourse->id, 'title' => 'Derecho Penitenciario'], ['order' => 1]);

        Topic::updateOrCreate(
            ['block_id' => $block1->id, 'title' => 'La ejecución de las penas'],
            ['slug' => 'la-ejecucion-de-las-penas', 'content' => 'Contenido profundo.', 'order' => 1, 'video_type' => 'explicativa']
        );

        $test = Test::updateOrCreate(
            ['course_id' => $mainCourse->id, 'title' => 'Examen Diagnóstico'],
            ['type' => 'simulacro', 'time_limit_minutes' => 60, 'passing_score' => 70]
        );

        $q1 = Question::updateOrCreate(
            ['test_id' => $test->id, 'text' => '¿Fin primordial de IIPP?'],
            ['explanation' => 'Art 25.2 CE.', 'type' => 'multiple_choice']
        );

        Option::updateOrCreate(['question_id' => $q1->id, 'text' => 'Reeducación'], ['is_correct' => true]);

        $plans = [
            ['slug' => 'monthly', 'name' => 'Mensual', 'price' => 9.99, 'days' => 30],
            ['slug' => 'yearly', 'name' => 'Anual', 'price' => 89.99, 'days' => 365],
        ];

        foreach ($plans as $p) {
            Plan::updateOrCreate(
                ['slug' => $p['slug']],
                ['name' => $p['name'], 'price' => $p['price'], 'duration_in_days' => $p['days']]
            );
        }
    }
}
