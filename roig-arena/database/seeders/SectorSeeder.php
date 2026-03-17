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
            $sectores[] = ['nombre' => "Sector $i", 'descripcion' => 'Grada lateral', 'activo' => true];
        }

        // Sectores 301-323
        for ($i = 301; $i <= 323; $i++) {
            $sectores[] = ['nombre' => "Sector $i", 'descripcion' => 'Grada superior', 'activo' => true];
        }

        // Palcos 1-22
        for ($i = 1; $i <= 22; $i++) {
            $sectores[] = ['nombre' => "Palco $i", 'descripcion' => 'Palco VIP', 'activo' => true];
        }

        // Sectores especiales
        $sectores[] = ['nombre' => 'CLUB', 'descripcion' => 'Zona Club', 'activo' => true];
        $sectores[] = ['nombre' => 'JOHNNIE WALKER', 'descripcion' => 'Zona Johnnie Walker', 'activo' => true];
        $sectores[] = ['nombre' => 'PISTA', 'descripcion' => 'Pista central', 'activo' => true];
        $sectores[] = ['nombre' => 'FRONT STAGE', 'descripcion' => 'Frente al escenario', 'activo' => true];

        foreach ($sectores as $sector) {
            Sector::create($sector);
        }

        $this->command->info('✅ Sectores creados: ' . count($sectores));
    }
}