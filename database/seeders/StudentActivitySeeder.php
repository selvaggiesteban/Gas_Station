<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\StudySession;
use App\Models\WeeklyChallenge;
use App\Models\ChallengeParticipation;
use App\Models\Topic;
use Carbon\Carbon;

class StudentActivitySeeder extends Seeder
{
    /**
     * Blindaje v13.0 - Student Activity
     * Sincronización con WeeklyChallenge y StudySessions finales.
     */
    public function run(): void
    {
        $student = User::role('Student')->first();
        if (!$student) return;

        // 1. Sesiones de Estudio (Campos reales)
        StudySession::updateOrCreate(
            ['user_id' => $student->id, 'started_at' => Carbon::now()->subHours(1)],
            [
                'duration_minutes' => 25,
                'completed' => true,
                'ended_at' => Carbon::now()->subMinutes(35)
            ]
        );

        // 2. Retos Semanales (Sincronización con tipos de meta de abril 2026)
        $admin = User::role('Admin')->first();
        
        $challenge = WeeklyChallenge::updateOrCreate(
            ['title' => 'Reto de la Semana: Constancia'],
            [
                'description' => 'Completa tus simulacros semanales.',
                'goal_type' => 'tests_completed',
                'goal_amount' => 1,
                'reward_points' => 50,
                'created_by' => $admin->id ?? 1,
                'start_date' => Carbon::now()->startOfWeek(),
                'end_date' => Carbon::now()->endOfWeek(),
                'is_active' => true,
            ]
        );

        ChallengeParticipation::updateOrCreate(
            ['challenge_id' => $challenge->id, 'user_id' => $student->id],
            ['is_completed' => false]
        );
    }
}
