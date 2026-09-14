<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Seguridad y Roles
            RolesAndPermissionsSeeder::class,
            UserRolesSeeder::class,

            // 2. Estructura y Contenido Base
            MasterProductionSeeder::class,
            AcademicusSeeder::class,
            AcademyStructureSeeder::class,
            AcademyContentSeeder::class,

            // 3. Complementos y Catálogo
            AvatarItemsSeeder::class,
            MotivationalQuotesSeeder::class,
            SubscriptionSeeder::class,
            MonthlyCoursesSeeder::class,
            ExampleTestsSeeder::class,
            
            // 4. Comunidad y Red Social
            CommunitySeeder::class,

            // 5. Datos de Actividad y Simulación (Carga Pesada)
            MassiveHistoricalSeeder::class,
            StudentActivitySeeder::class,
            UserActivitySeeder::class,
            GlobalMetricsSeeder::class,
        ]);
    }
}
