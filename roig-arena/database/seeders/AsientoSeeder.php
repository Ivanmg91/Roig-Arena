<?php

namespace Database\Seeders;

use App\Models\Sector;
use App\Models\Asiento;
use Illuminate\Database\Seeder;

class AsientoSeeder extends Seeder
{
    public function run(): void
    {
        $sectores = Sector::all();
        $totalAsientos = 0;

        foreach ($sectores as $sector) {
            $asientos = $this->generarAsientosPorSector($sector);
            $totalAsientos += count($asientos);
        }

        // Llamamos a la función para generar estado_asientos para el primer evento
        $this->generarEstadoAsientosParaEvento($asientos, 1); // Asumiendo que el primer evento tiene ID 1

        $this->command->info("✅ Asientos creados: {$totalAsientos}");
    }

    private function generarAsientosPorSector(Sector $sector): array
    {
        $asientos = [];

        // Sectores 101-122 y 301-323: 20 filas x 15 asientos = 300 asientos
        if (preg_match('/^Sector (10[1-9]|1[1-2][0-9]|30[1-9]|3[1-2][0-9])$/', $sector->nombre)) {
            for ($fila = 1; $fila <= 20; $fila++) {
                for ($numero = 1; $numero <= 15; $numero++) {
                    $asientos[] = Asiento::create([
                        'sector_id' => $sector->id,
                        'fila' => (string) $fila,
                        'numero' => $numero,
                    ]);
                }
            }
        }
        // Palcos: 1 fila x 8 asientos = 8 asientos
        elseif (str_starts_with($sector->nombre, 'Palco')) {
            for ($numero = 1; $numero <= 8; $numero++) {
                $asientos[] = Asiento::create([
                    'sector_id' => $sector->id,
                    'fila' => 'A',
                    'numero' => $numero,
                ]);
            }
        }
        // CLUB: 10 filas x 20 asientos = 200 asientos
        elseif ($sector->nombre === 'CLUB') {
            for ($fila = 1; $fila <= 10; $fila++) {
                for ($numero = 1; $numero <= 20; $numero++) {
                    $asientos[] = Asiento::create([
                        'sector_id' => $sector->id,
                        'fila' => (string) $fila,
                        'numero' => $numero,
                    ]);
                }
            }
        }
        // JOHNNIE WALKER: 8 filas x 15 asientos = 120 asientos
        elseif ($sector->nombre === 'JOHNNIE WALKER') {
            for ($fila = 1; $fila <= 8; $fila++) {
                for ($numero = 1; $numero <= 15; $numero++) {
                    $asientos[] = Asiento::create([
                        'sector_id' => $sector->id,
                        'fila' => (string) $fila,
                        'numero' => $numero,
                    ]);
                }
            }
        }
        // PISTA: 30 filas x 25 asientos = 750 asientos
        elseif ($sector->nombre === 'PISTA') {
            for ($fila = 1; $fila <= 30; $fila++) {
                for ($numero = 1; $numero <= 25; $numero++) {
                    $asientos[] = Asiento::create([
                        'sector_id' => $sector->id,
                        'fila' => (string) $fila,
                        'numero' => $numero,
                    ]);
                }
            }
        }
        // FRONT STAGE: 5 filas x 30 asientos = 150 asientos
        elseif ($sector->nombre === 'FRONT STAGE') {
            for ($fila = 1; $fila <= 5; $fila++) {
                for ($numero = 1; $numero <= 30; $numero++) {
                    $asientos[] = Asiento::create([
                        'sector_id' => $sector->id,
                        'fila' => (string) $fila,
                        'numero' => $numero,
                    ]);
                }
            }
        }

        return $asientos;
    }

    private function generarEstadoAsientosParaEvento($asientos, $eventoId): void
    {
        foreach ($asientos as $asiento) {
            $asiento->estadoAsientos()->create([
                'evento_id' => $eventoId,
                'asiento_id' => $asiento->id,
                'estado' => 1, // DISPONIBLE
                'reservado_hasta' => null,
            ]);
        }
    }
}