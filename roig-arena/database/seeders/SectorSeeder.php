<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectores = [
            // Lateral derecho.
            ['nombre' => 'ESTE 201', 'descripcion' => 'Grada lateral este', 'cantidad_filas' => 10, 'cantidad_columnas' => 12, 'color_hex' => '#4E87D9', 'activo' => true],
            ['nombre' => 'ESTE 202', 'descripcion' => 'Grada lateral este', 'cantidad_filas' => 10, 'cantidad_columnas' => 12, 'color_hex' => '#447BCA', 'activo' => true],
            ['nombre' => 'ESTE 203', 'descripcion' => 'Grada lateral este', 'cantidad_filas' => 10, 'cantidad_columnas' => 12, 'color_hex' => '#3C6FBA', 'activo' => true],

            // Banda inferior.
            ['nombre' => 'SUR 301', 'descripcion' => 'Anillo sur opuesto al escenario', 'cantidad_filas' => 12, 'cantidad_columnas' => 14, 'color_hex' => '#46AA73', 'activo' => true],
            ['nombre' => 'SUR 302', 'descripcion' => 'Anillo sur opuesto al escenario', 'cantidad_filas' => 12, 'cantidad_columnas' => 14, 'color_hex' => '#3E9B67', 'activo' => true],
            ['nombre' => 'SUR 303', 'descripcion' => 'Anillo sur opuesto al escenario', 'cantidad_filas' => 12, 'cantidad_columnas' => 14, 'color_hex' => '#378C5D', 'activo' => true],

            // Lateral izquierdo.
            ['nombre' => 'OESTE 401', 'descripcion' => 'Grada lateral oeste', 'cantidad_filas' => 10, 'cantidad_columnas' => 12, 'color_hex' => '#8D74D1', 'activo' => true],
            ['nombre' => 'OESTE 402', 'descripcion' => 'Grada lateral oeste', 'cantidad_filas' => 10, 'cantidad_columnas' => 12, 'color_hex' => '#7C63BE', 'activo' => true],
            ['nombre' => 'OESTE 403', 'descripcion' => 'Grada lateral oeste', 'cantidad_filas' => 10, 'cantidad_columnas' => 12, 'color_hex' => '#6E56AB', 'activo' => true],

            // Zona interior (pista) como sectores de asientos.
            ['nombre' => 'PISTA A', 'descripcion' => 'Sector interior de pista', 'cantidad_filas' => 8, 'cantidad_columnas' => 10, 'color_hex' => '#E07F3F', 'activo' => true],
            ['nombre' => 'PISTA B', 'descripcion' => 'Sector interior de pista', 'cantidad_filas' => 8, 'cantidad_columnas' => 10, 'color_hex' => '#D67236', 'activo' => true],
            ['nombre' => 'PISTA C', 'descripcion' => 'Sector interior de pista', 'cantidad_filas' => 8, 'cantidad_columnas' => 10, 'color_hex' => '#C9652E', 'activo' => true],
        ];

        foreach ($sectores as $sector) {
            Sector::create($sector);
        }

        $this->command->info('✅ Sectores creados: ' . count($sectores));
    }
}
