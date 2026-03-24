<?php

namespace Database\Seeders;

use App\Models\Artista;
use App\Models\Evento;
use Illuminate\Database\Seeder;

class ArtistaSeeder extends Seeder
{
    public function run(): void
    {
        $eventos = Evento::all();
        
        $artistas = [
            [
                'nombre' => 'Fito & Fitipaldis',
                'evento_id' => $eventos->random()->id,
                'descripcion' => 'Fito & Fitipaldis es una banda de rock española liderada por el cantante y guitarrista Fito Cabrales. Con más de 20 años de trayectoria, han conquistado a miles de fans con su estilo único que mezcla rock, blues y soul.',
                'imagen_url' => 'https://via.placeholder.com/640x480?text=Fito+%26+Fitipaldis',
            ],
            [
                'nombre' => 'Shakira',
                'evento_id' => $eventos->random()->id,
                'descripcion' => 'Shakira es una cantante, compositora y bailarina colombiana reconocida mundialmente por su talento y carisma. Con una carrera que abarca más de dos décadas, ha vendido millones de discos y ha ganado numerosos premios internacionales.',
                'imagen_url' => 'https://via.placeholder.com/640x480?text=Shakira',
            ],
            [
                'nombre' => 'Coldplay',
                'evento_id' => $eventos->random()->id,
                'descripcion' => 'Coldplay es una banda de rock alternativo británica formada en 1996. Conocidos por sus melodías emotivas y letras introspectivas, han alcanzado el éxito global con álbumes como "Parachutes" y "A Rush of Blood to the Head".',
                'imagen_url' => 'https://via.placeholder.com/640x480?text=Coldplay',
            ],
            [
                'nombre' => 'Beyoncé',
                'evento_id' => $eventos->random()->id,
                'descripcion' => 'Beyoncé es una cantante, actriz y empresaria estadounidense considerada una de las artistas más influyentes de la música contemporánea. Con una carrera que abarca más de dos décadas, ha ganado numerosos premios Grammy y ha vendido millones de discos en todo el mundo.',
                'imagen_url' => 'https://via.placeholder.com/640x480?text=Beyonc%C3%A9',
            ],
        ];

        foreach ($artistas as $artista) {
            Artista::create($artista);
        }

        $this->command->info('✅ Artistas creados: ' . count($artistas));
    }
}