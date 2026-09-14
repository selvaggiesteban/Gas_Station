<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Block;
use App\Models\Topic;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MonthlyCoursesSeeder extends Seeder
{
    public function run(): void
    {
        $months = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];

        foreach ($months as $index => $month) {
            $course = Course::updateOrCreate(
                ['slug' => Str::slug("Curso Mensual $month")],
                [
                    'title' => "Curso Mensual - $month",
                    'description' => "Preparación integral para el mes de $month.",
                    'price' => 59.90,
                    'is_active' => true,
                ]
            );

            Product::updateOrCreate(
                ['course_id' => $course->id, 'type' => 'monthly'],
                [
                    'name' => "Suscripción Mensual - $month",
                    'description' => "Acceso completo al contenido de $month",
                    'price' => 59.90,
                    'duration_days' => 30,
                    'stripe_price_id' => 'price_mock_' . strtolower($month),
                    'is_active' => true,
                    'display_order' => $index,
                ]
            );

            for ($i = 1; $i <= 4; $i++) {
                $block = Block::updateOrCreate(
                    ['course_id' => $course->id, 'order' => $i],
                    ['title' => "Semana $i: Bloque de Contenidos"]
                );

                Topic::updateOrCreate(
                    ['block_id' => $block->id, 'order' => 1],
                    [
                        'title' => "Introducción y Material de la Semana $i",
                        'slug' => Str::slug("material-semana-$i-$month"),
                        'content' => "Bienvenido al material de la semana $i.",
                        'video_type' => 'explicativa',
                    ]
                );
            }
        }
    }
}
