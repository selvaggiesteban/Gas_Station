<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Block;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Call RolesAndPermissionsSeeder
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Create default Admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@atreveteomuere.com'],
            [
                'name' => 'Administrador',
                'password' => bcrypt('admin123'),
                'email_verified_at' => now(),
            ]
        );
        
        if (!$admin->hasRole('Admin')) {
            $admin->assignRole('Admin');
        }

        // 3. Scaffold a basic Course with 1 Block and 1 Topic
        $course = Course::updateOrCreate(
            ['slug' => 'curso-basico'],
            [
                'title' => 'Curso de Iniciación',
                'description' => 'Un curso básico para comenzar en la plataforma.',
                'is_active' => true,
            ]
        );

        $block = Block::updateOrCreate(
            ['course_id' => $course->id, 'title' => 'Bloque General'],
            [
                'order' => 1,
            ]
        );

        Topic::updateOrCreate(
            ['block_id' => $block->id, 'title' => 'Introducción'],
            [
                'slug' => 'introduccion',
                'order' => 1,
                'is_active' => true,
            ]
        );
    }
}
