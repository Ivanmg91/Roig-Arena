<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectores = [];

        // Sectores 101-122
        for ($i = 101; $i <= 122; $i++) {
            $sectores[] = ['nombre' => "Sector $i", 'descripcion' => 'Grada lateral', 'cantidad_filas' => 10, 'cantidad_columnas' => 10, 'color_hex' => '#CCCCCC', 'activo' => true];
        }

        // Sectores 301-323
        for ($i = 301; $i <= 323; $i++) {
            $sectores[] = ['nombre' => "Sector $i", 'descripcion' => 'Grada superior', 'cantidad_filas' => 10, 'cantidad_columnas' => 10, 'color_hex' => '#DDDDDD', 'activo' => true];
        }

        // Palcos 1-22
        for ($i = 1; $i <= 22; $i++) {
            $sectores[] = ['nombre' => "Palco $i", 'descripcion' => 'Palco VIP', 'cantidad_filas' => 10, 'cantidad_columnas' => 10, 'color_hex' => '#EEEEEE', 'activo' => true];
        }

        // Sectores especiales
        $sectores[] = ['nombre' => 'CLUB', 'descripcion' => 'Zona Club', 'cantidad_filas' => 10, 'cantidad_columnas' => 10, 'color_hex' => '#FF0000', 'activo' => true];
        $sectores[] = ['nombre' => 'JOHNNIE WALKER', 'descripcion' => 'Zona Johnnie Walker', 'cantidad_filas' => 10, 'cantidad_columnas' => 10, 'color_hex' => '#00FF00', 'activo' => true];
        $sectores[] = ['nombre' => 'PISTA', 'descripcion' => 'Pista central', 'cantidad_filas' => 10, 'cantidad_columnas' => 10, 'color_hex' => '#0000FF', 'activo' => true];
        $sectores[] = ['nombre' => 'FRONT STAGE', 'descripcion' => 'Frente al escenario', 'cantidad_filas' => 10, 'cantidad_columnas' => 10, 'color_hex' => '#FFFF00', 'activo' => true];

        foreach ($sectores as $sector) {
            Sector::create($sector);
        }

        $this->command->info('✅ Sectores creados: ' . count($sectores));
    }
}