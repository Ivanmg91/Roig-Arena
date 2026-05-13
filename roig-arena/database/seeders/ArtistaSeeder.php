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
                'imagen_url' => 'https://estaticos-cdn.prensaiberica.es/clip/587bb836-d29a-4130-884c-124f1c5b5021_alta-libre-aspect-ratio_default_0_x612y260.jpg',
            ],
            [
                'nombre' => 'Shakira',
                'evento_id' => $eventos->random()->id,
                'descripcion' => 'Shakira es una cantante, compositora y bailarina colombiana reconocida mundialmente por su talento y carisma. Con una carrera que abarca más de dos décadas, ha vendido millones de discos y ha ganado numerosos premios internacionales.',
                'imagen_url' => 'https://cdn-images.dzcdn.net/images/artist/69c569506a8ff6ab0edfecbd1adf94b0/1900x1900-000000-80-0-0.jpg',
            ],
            [
                'nombre' => 'Coldplay',
                'evento_id' => $eventos->random()->id,
                'descripcion' => 'Coldplay es una banda de rock alternativo británica formada en 1996. Conocidos por sus melodías emotivas y letras introspectivas, han alcanzado el éxito global con álbumes como "Parachutes" y "A Rush of Blood to the Head".',
                'imagen_url' => 'https://i.scdn.co/image/ab676161000051741ba8fc5f5c73e7e9313cc6eb',
            ],
            [
                'nombre' => 'Beyoncé',
                'evento_id' => $eventos->random()->id,
                'descripcion' => 'Beyoncé es una cantante, actriz y empresaria estadounidense considerada una de las artistas más influyentes de la música contemporánea. Con una carrera que abarca más de dos décadas, ha ganado numerosos premios Grammy y ha vendido millones de discos en todo el mundo.',
                'imagen_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7d/Beyonc%C3%A9_-_Tottenham_Hotspur_Stadium_-_1st_June_2023_%2871_of_118%29_%2852945301662%29_%28face_cropped%29.jpg/250px-Beyonc%C3%A9_-_Tottenham_Hotspur_Stadium_-_1st_June_2023_%2871_of_118%29_%2852945301662%29_%28face_cropped%29.jpg',
            ],
        ];

        foreach ($artistas as $artista) {
            $eventoId = $artista['evento_id'] ?? null;
            unset($artista['evento_id']);

            $a = Artista::create($artista);

            if ($eventoId) {
                $a->eventos()->attach($eventoId);
            }
        }

        $this->command->info('✅ Artistas creados: ' . count($artistas));
    }
}