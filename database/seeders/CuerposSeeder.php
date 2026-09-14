<?php

namespace Database\Seeders;

use App\Models\Cuerpo;
use Illuminate\Database\Seeder;

class CuerposSeeder extends Seeder
{
    public function run(): void
    {
        $cuerpos = [
            'Ayudantes IIPP',
            'Jurista IIPP',
            'Enfermero/a IIPP',
            'TCAE IIPP',
            'Trabajo Social IIPP',
            'Cuerpo Social IIPP',
            'Facultativo IIPP',
            'Farmaceutico IIPP',
            'Psicologo/a IIPP',
            'Enfermero/a Militar',
        ];

        foreach ($cuerpos as $name) {
            Cuerpo::firstOrCreate(
                ['name' => $name],
                ['slug' => \Illuminate\Support\Str::slug($name), 'is_active' => true]
            );
        }
    }
}
