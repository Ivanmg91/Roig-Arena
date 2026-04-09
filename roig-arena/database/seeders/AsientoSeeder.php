<?php

namespace Database\Seeders;

use App\Models\Sector;
use App\Models\Asiento;
use Illuminate\Database\Seeder;

class AsientoSeeder extends Seeder
{

    private array $asientos = [];

    public function run(): void
    {
        $sectores = Sector::activos()->get();
        $totalAsientos = 0;
        $this->asientos = [];

        foreach ($sectores as $sector) {
            $asientosSector = $this->generarAsientosPorSector($sector);
            $this->asientos = array_merge($this->asientos, $asientosSector);
            $totalAsientos += count($asientosSector);
        }

        // Llamamos a la función para generar estado_asientos para el primer evento
        $this->generarEstadoAsientosParaEvento($this->asientos, 1); // Asumiendo que el primer evento tiene ID 1

        $this->command->info("✅ Asientos creados: {$totalAsientos}");
    }

    private function generarAsientosPorSector(Sector $sector): array
    {
        $asientos = [];

        // Oeste 401, 402, 403: 3 filas x 5 asientos = 15 asientos por sector
        if (preg_match('/^Oeste (401|402|403)$/i', $sector->nombre)) {
            for ($fila = 1; $fila <= 3; $fila++) {
                for ($numero = 1; $numero <= 5; $numero++) {
                    $asientos[] = Asiento::create([
                        'sector_id' => $sector->id,
                        'fila' => (string) $fila,
                        'numero' => $numero,
                    ]);
                }
            }
        }

        // Sur 301, 302, 303: 3 filas x 5 asientos = 15 asientos por sector
        elseif (preg_match('/^Sur (301|302|303)$/i', $sector->nombre)) {
            for ($fila = 1; $fila <= 3; $fila++) {
                for ($numero = 1; $numero <= 5; $numero++) {
                    $asientos[] = Asiento::create([
                        'sector_id' => $sector->id,
                        'fila' => (string) $fila,
                        'numero' => $numero,
                    ]);
                }
            }
        }

        // Este 201, 202, 203: 3 filas x 5 asientos = 15 asientos por sector
        elseif (preg_match('/^Este (201|202|203)$/i', $sector->nombre)) {
            for ($fila = 1; $fila <= 3; $fila++) {
                for ($numero = 1; $numero <= 5; $numero++) {
                    $asientos[] = Asiento::create([
                        'sector_id' => $sector->id,
                        'fila' => (string) $fila,
                        'numero' => $numero,
                    ]);
                }
            }
        }

        // Pistas: 1 fila x 8 asientos = 8 asientos
        elseif (str_starts_with($sector->nombre, 'PISTA')) {
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
            for ($fila = 1; $fila <= 3; $fila++) {
                for ($numero = 1; $numero <= 5; $numero++) {
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
            for ($fila = 1; $fila <= 3; $fila++) {
                for ($numero = 1; $numero <= 5; $numero++) {
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
                for ($numero = 1; $numero <= 5; $numero++) {
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
