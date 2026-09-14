<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserActivitySeeder extends Seeder
{
    /**
     * Sincronización v15.0 - User Activity
     * ELIMINADA DEPENDENCIA DE FAKER (Production Safe)
     */
    public function run(): void
    {
        $students = User::role('Student')->take(5)->get();

        if ($students->isEmpty()) {
            $this->command->info('No hay estudiantes para sembrar actividad.');
            return;
        }

        $routes = ['dashboard', 'temas.ver', 'examenes.show', 'perfil.ver', 'cursos.catalogo'];
        $actions = ['view', 'click', 'submit', 'heartbeat'];

        foreach ($students as $student) {
            for ($i = 0; $i < 20; $i++) {
                UserActivity::create([
                    'user_id' => $student->id,
                    'session_id' => Str::random(40),
                    'url' => '/' . $routes[array_rand($routes)],
                    'route_name' => $routes[array_rand($routes)],
                    'method' => 'GET',
                    'action_type' => $actions[array_rand($actions)],
                    'ip_address' => '127.0.0.1',
                    'device_info' => ['browser' => 'Chrome', 'os' => 'Windows'],
                    'time_spent' => rand(10, 300),
                    'created_at' => now()->subMinutes(rand(1, 10000)),
                ]);
            }
        }

        $this->command->info('Actividad de usuario sembrada sin depender de Faker.');
    }
}
