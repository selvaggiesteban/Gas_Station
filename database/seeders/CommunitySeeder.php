<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Topic;
use App\Models\TutoringQuestion;
use Carbon\Carbon;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        $student = User::where('email', 'student@example.com')->first();
        $teacher = User::role('Teacher')->first();
        $topic = Topic::first();

        if ($student && $teacher && $topic) {
            // Idempotencia para Tutorías
            TutoringQuestion::updateOrCreate(
                ['title' => 'Duda sobre los Grados de Tratamiento', 'user_id' => $student->id],
                [
                    'course_id' => $topic->block->course_id,
                    'topic_id' => $topic->id,
                    'content' => 'Hola profe, no me termina de quedar clara la diferencia...',
                    'status' => 'answered',
                    'is_public' => true,
                    'answered_by' => $teacher->id,
                    'answer_content' => '¡Hola! Es muy sencillo. Los internos...',
                    'answered_at' => Carbon::now()->subDays(2),
                ]
            );

            TutoringQuestion::updateOrCreate(
                ['title' => 'Incoación de un expediente disciplinario', 'user_id' => $student->id],
                [
                    'course_id' => $topic->block->course_id,
                    'topic_id' => $topic->id,
                    'content' => 'En el test de la semana pasada...',
                    'status' => 'pending',
                    'is_public' => false,
                ]
            );
        }
    }
}
