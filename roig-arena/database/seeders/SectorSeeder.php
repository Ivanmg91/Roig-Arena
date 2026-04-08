<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectores = [];

        // Sectores 101-103
        for ($i = 101; $i <= 103; $i++) {
            $sectores[] = ['nombre' => "Sector $i", 'descripcion' => 'Grada lateral', 'cantidad_filas' => 10, 'cantidad_columnas' => 10, 'color_hex' => '#CCCCCC', 'activo' => true];
        }

        // Sectores 301-303
        for ($i = 301; $i <= 303; $i++) {
            $sectores[] = ['nombre' => "Sector $i", 'descripcion' => 'Grada superior', 'cantidad_filas' => 10, 'cantidad_columnas' => 10, 'color_hex' => '#DDDDDD', 'activo' => false];
        }

        // Palcos 1-202
        for ($i = 1; $i <= 3; $i++) {
            $sectores[] = ['nombre' => "Palco $i", 'descripcion' => 'Palco VIP', 'cantidad_filas' => 10, 'cantidad_columnas' => 10, 'color_hex' => '#EEEEEE', 'activo' => false];
        }

        // Sectores especiales
        $sectores[] = ['nombre' => 'CLUB', 'descripcion' => 'Zona Club', 'cantidad_filas' => 10, 'cantidad_columnas' => 10, 'color_hex' => '#FF0000', 'activo' => true];
        $sectores[] = ['nombre' => 'JOHNNIE WALKER', 'descripcion' => 'Zona Johnnie Walker', 'cantidad_filas' => 10, 'cantidad_columnas' => 10, 'color_hex' => '#00FF00', 'activo' => false];
        $sectores[] = ['nombre' => 'PISTA', 'descripcion' => 'Pista central', 'cantidad_filas' => 10, 'cantidad_columnas' => 10, 'color_hex' => '#0000FF', 'activo' => false];
        $sectores[] = ['nombre' => 'FRONT STAGE', 'descripcion' => 'Frente al escenario', 'cantidad_filas' => 10, 'cantidad_columnas' => 10, 'color_hex' => '#FFFF00', 'activo' => false];

        foreach ($sectores as $sector) {
            Sector::create($sector);
        }

        $this->command->info('✅ Sectores creados: ' . count($sectores));
    }
}
