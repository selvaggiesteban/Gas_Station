<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\AcademyAnnouncement;
use App\Models\Schedule;
use App\Models\TutoringQuestion;
use Carbon\Carbon;

class AcademyContentSeeder extends Seeder
{
    /**
     * Versión v10.0 - Limpieza de Espectros
     * Eliminados para siempre: scheduled_date e is_from_academy.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@atreveteomuere.com')->first() ?? User::first();
        if (!$admin) return;

        $course = Course::first();
        if (!$course) return;

        AcademyAnnouncement::updateOrCreate(
            ['title' => '¡Bienvenidos!'],
            ['content' => 'Material actualizado.', 'is_active' => true, 'published_at' => now(), 'published_by' => $admin->id]
        );

        // SOLO USAMOS CAMPOS DE LA MIGRACIÓN REAL
        Schedule::updateOrCreate(
            ['title' => "Simulacro Mensual", 'user_id' => $admin->id],
            [
                'description' => "Evaluación obligatoria.",
                'start_time' => Carbon::now()->next(Carbon::SATURDAY)->setHour(10),
                'end_time' => Carbon::now()->next(Carbon::SATURDAY)->setHour(13),
                'is_official' => true,
            ]
        );
    }
}
