<?php

namespace Database\Seeders;

use App\Models\AvatarItem;
use Illuminate\Database\Seeder;

class AvatarItemsSeeder extends Seeder
{
    /**
     * Sincronización v11.0 - Avatar Items
     * Corregido: Nombres de constantes (RANK_BRONCE en lugar de RANK_BRONZE)
     * Añadido: Idempotencia con updateOrCreate
     */
    public function run(): void
    {
        $items = [
            // Uniforms
            [
                'name' => 'Uniforme Básico',
                'description' => 'El uniforme estándar de todo estudiante de Academicus.',
                'type' => AvatarItem::TYPE_UNIFORM,
                'image_path' => 'avatars/uniforms/basic.png',
                'unlock_level' => 1,
                'is_default' => true,
                'position' => 1,
            ],
            [
                'name' => 'Uniforme de Bronce',
                'description' => 'Para los valientes que comienzan su viaje.',
                'type' => AvatarItem::TYPE_UNIFORM,
                'image_path' => 'avatars/uniforms/bronze.png',
                'unlock_level' => 1,
                'unlock_rank' => AvatarItem::RANK_BRONCE,
                'is_default' => false,
                'position' => 2,
            ],
            [
                'name' => 'Uniforme de Plata',
                'description' => 'Muestra tu dedicación con este elegante uniforme plateado.',
                'type' => AvatarItem::TYPE_UNIFORM,
                'image_path' => 'avatars/uniforms/silver.png',
                'unlock_level' => 3,
                'unlock_rank' => AvatarItem::RANK_PLATA,
                'position' => 3,
            ],
            [
                'name' => 'Uniforme de Oro',
                'description' => 'Reservado para los maestros del conocimiento.',
                'type' => AvatarItem::TYPE_UNIFORM,
                'image_path' => 'avatars/uniforms/gold.png',
                'unlock_level' => 5,
                'unlock_rank' => AvatarItem::RANK_ORO,
                'position' => 4,
            ],

            // Frames
            [
                'name' => 'Marco de Madera',
                'description' => 'Un marco sencillo pero elegante.',
                'type' => AvatarItem::TYPE_FRAME,
                'image_path' => 'avatars/frames/wood.png',
                'unlock_level' => 1,
                'is_default' => true,
                'position' => 1,
            ],
            [
                'name' => 'Marco de Bronce',
                'description' => 'Marco metálico con acabado en bronce.',
                'type' => AvatarItem::TYPE_FRAME,
                'image_path' => 'avatars/frames/bronze.png',
                'unlock_level' => 1,
                'unlock_rank' => AvatarItem::RANK_BRONCE,
                'position' => 2,
            ],
            [
                'name' => 'Marco de Plata',
                'description' => 'Un marco brillante que refleja tu progreso.',
                'type' => AvatarItem::TYPE_FRAME,
                'image_path' => 'avatars/frames/silver.png',
                'unlock_level' => 3,
                'unlock_rank' => AvatarItem::RANK_PLATA,
                'position' => 3,
            ],
            [
                'name' => 'Marco de Oro',
                'description' => 'El marco más prestigioso de Academicus.',
                'type' => AvatarItem::TYPE_FRAME,
                'image_path' => 'avatars/frames/gold.png',
                'unlock_level' => 5,
                'unlock_rank' => AvatarItem::RANK_ORO,
                'position' => 4,
            ],

            // Badges
            [
                'name' => 'Insignia de Novato',
                'description' => 'Bienvenido a bordo, estudiante.',
                'type' => AvatarItem::TYPE_BADGE,
                'image_path' => 'avatars/badges/novice.png',
                'unlock_level' => 1,
                'is_default' => true,
                'position' => 1,
            ],
            [
                'name' => 'Insignia de Guerrero',
                'description' => 'Has demostrado valor en las batallas de conocimiento.',
                'type' => AvatarItem::TYPE_BADGE,
                'image_path' => 'avatars/badges/warrior.png',
                'unlock_level' => 3,
                'unlock_battles_won' => 5,
                'position' => 2,
            ],
            [
                'name' => 'Insignia de Maestro',
                'description' => 'Tu sabiduría es reconocida por todos.',
                'type' => AvatarItem::TYPE_BADGE,
                'image_path' => 'avatars/badges/master.png',
                'unlock_level' => 5,
                'unlock_battles_won' => 10,
                'position' => 3,
            ],
            [
                'name' => 'Insignia de Campeón',
                'description' => 'Un campeón nace de la victoria y la dedicación.',
                'type' => AvatarItem::TYPE_BADGE,
                'image_path' => 'avatars/badges/champion.png',
                'unlock_level' => 10,
                'unlock_battles_won' => 25,
                'position' => 4,
            ],
            [
                'name' => 'Insignia de Productividad',
                'description' => 'La constancia es la clave del éxito.',
                'type' => AvatarItem::TYPE_BADGE,
                'image_path' => 'avatars/badges/productivity.png',
                'unlock_level' => 2,
                'unlock_pomodoros' => 25,
                'position' => 5,
            ],

            // Accessories
            [
                'name' => 'Corona de Victorias',
                'description' => 'Por dominar a tus oponentes en batalla.',
                'type' => AvatarItem::TYPE_ACCESSORY,
                'image_path' => 'avatars/accessories/crown.png',
                'unlock_level' => 5,
                'unlock_battles_won' => 10,
                'position' => 1,
            ],
            [
                'name' => 'Auriculares de Estudio',
                'description' => 'Para concentrarte al máximo.',
                'type' => AvatarItem::TYPE_ACCESSORY,
                'image_path' => 'avatars/accessories/headphones.png',
                'unlock_level' => 3,
                'unlock_pomodoros' => 50,
                'position' => 2,
            ],
            [
                'name' => 'Trofeo del Conocimiento',
                'description' => 'Reconocimiento a tu dedicación al estudio.',
                'type' => AvatarItem::TYPE_ACCESSORY,
                'image_path' => 'avatars/accessories/trophy.png',
                'unlock_level' => 7,
                'unlock_pomodoros' => 100,
                'position' => 3,
            ],
        ];

        foreach ($items as $item) {
            AvatarItem::updateOrCreate(['name' => $item['name']], $item);
        }
    }
}
