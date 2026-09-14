<?php

namespace Database\Seeders;

use App\Models\MotivationalQuote;
use Illuminate\Database\Seeder;

class MotivationalQuotesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quotes = [
            ['text' => 'El éxito es la suma de pequeños esfuerzos repetidos día tras día.', 'author' => 'Robert Collier'],
            ['text' => 'No cuentes los días, haz que los días cuenten.', 'author' => 'Muhammad Ali'],
            ['text' => 'Tu única competencia es la persona que eras ayer.', 'author' => 'Anónimo'],
            ['text' => 'La disciplina es el puente entre las metas y los logros.', 'author' => 'Jim Rohn'],
            ['text' => 'Cree que puedes y ya estarás a medio camino.', 'author' => 'Theodore Roosevelt'],
            ['text' => 'El estudio no se mide por el número de horas, sino por el nivel de concentración.', 'author' => 'Anónimo'],
            ['text' => 'La constancia es la virtud por la cual todas las demás virtudes dan fruto.', 'author' => 'Arturo Graf'],
        ];

        foreach ($quotes as $quote) {
            MotivationalQuote::create($quote + ['is_active' => true, 'category' => 'study']);
        }
    }
}
