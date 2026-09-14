<?php

namespace App\Services;

use App\Models\Topic;
use App\Models\Course;
use App\Models\Test;
use Illuminate\Support\Str;

class ActivityLoggerService
{
    /**
     * Translate a route and URL into a human-readable action description.
     */
    public function getActionDescription(?string $routeName, string $url): string
    {
        // Topic viewing
        if ($routeName === 'temas.ver' || Str::contains($url, '/temas/')) {
            $slug = Str::afterLast($url, '/');
            $topic = Topic::where('slug', $slug)->first();
            if ($topic) {
                return "Visualizando Tema: " . $topic->title;
            }
        }

        // Exam/Test taking
        if ($routeName === 'examenes.presentar') {
            $id = Str::afterLast($url, '/');
            $test = Test::find($id);
            if ($test) {
                return "Realizando Examen: " . $test->title;
            }
        }

        // Course viewing
        if ($routeName === 'cursos.ver') {
            $id = Str::afterLast($url, '/');
            $course = Course::find($id);
            if ($course) {
                return "Explorando Curso: " . $course->title;
            }
        }

        // Generic mapping based on route name
        $mappings = [
            'dashboard' => 'Acceso al Panel Principal',
            'perfil.editar' => 'Editando Perfil',
            'cursos.mis-cursos' => 'Viendo Mis Cursos',
            'batallas.lista' => 'Viendo Lista de Batallas',
            'cronograma.oficial' => 'Consultando Cronograma Oficial',
            'cronograma.personal' => 'Consultando Cronograma Personal',
            'telemetria.heartbeat' => 'heartbeat',
        ];

        return $mappings[$routeName] ?? ($routeName ?: "Navegando: $url");
    }

    /**
     * Determine action type based on route or URL.
     */
    public function getActionType(?string $routeName, string $url): string
    {
        if ($routeName === 'telemetria.heartbeat') {
            return 'heartbeat';
        }

        if (Str::contains($routeName, 'enviar') || Str::contains($routeName, 'completar')) {
            return 'submit';
        }

        if (Str::contains($url, '/descargar/')) {
            return 'download';
        }

        return 'view';
    }
}
