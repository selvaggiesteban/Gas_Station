<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Block;
use App\Models\Topic;
use App\Models\ZoomMeeting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AcademyStructureSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::updateOrCreate(
            ['title' => 'Oposición Ayudantes de Instituciones Penitenciarias 2026'],
            [
                'slug' => Str::slug('Oposición Ayudantes de Instituciones Penitenciarias 2026'),
                'description' => 'Curso completo y actualizado al último BOE para superar la oposición.',
                'is_active' => true,
            ]
        );

        $teacher = User::role('Teacher')->first();

        $blockNames = ['Organización del Estado', 'Derecho Penal', 'Derecho Penitenciario'];
        
        foreach ($blockNames as $index => $name) {
            $block = Block::updateOrCreate(
                ['course_id' => $course->id, 'title' => "Bloque " . ($index + 1) . ": " . $name],
                ['order' => $index + 1]
            );

            for ($t = 1; $t <= 5; $t++) {
                $topicNum = ($index * 5) + $t;
                $topic = Topic::updateOrCreate(
                    ['block_id' => $block->id, 'title' => "Tema {$topicNum}: Contenido de ejemplo para " . $name],
                    ['order' => $t, 'video_type' => 'explicativa']
                );

                if ($t === 1 && $teacher) {
                    ZoomMeeting::updateOrCreate(
                        ['topic_id' => $topic->id, 'created_by' => $teacher->id],
                        [
                            'meeting_id' => '8' . rand(1000000000, 9999999999),
                            'join_url' => 'https://zoom.us/j/example',
                            'start_url' => 'https://zoom.us/s/example',
                            'start_time' => Carbon::now()->addDays(rand(1, 7))->setHour(18)->setMinute(0),
                            'duration_minutes' => 90,
                            'timezone' => 'Europe/Madrid',
                            'status' => 'scheduled',
                        ]
                    );
                }
            }
        }
    }
}
