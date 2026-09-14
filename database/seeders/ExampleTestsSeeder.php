<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Test;
use App\Models\Question;
use App\Models\Option;

class ExampleTestsSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::firstOrCreate(['title' => 'Curso Test'], ['is_active' => true]);

        $testTypes = [
            'test' => ['name' => 'Test Rápido', 'time' => 15],
            'simulacro' => ['name' => 'Simulacro Final', 'time' => 120],
        ];

        foreach ($testTypes as $type => $info) {
            $test = Test::updateOrCreate(
                ['course_id' => $course->id, 'title' => $info['name']],
                ['type' => $type, 'time_limit_minutes' => $info['time'], 'passing_score' => 50]
            );

            $question = Question::updateOrCreate(
                ['test_id' => $test->id, 'text' => "¿Pregunta de {$info['name']}?"],
                ['type' => 'multiple_choice']
            );

            Option::updateOrCreate(['question_id' => $question->id, 'text' => 'Opción A'], ['is_correct' => true]);
        }
    }
}
