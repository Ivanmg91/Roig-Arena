<?php

namespace Database\Seeders;

use App\Models\Evento;
use Illuminate\Database\Seeder;

class EventoSeeder extends Seeder
{
    public function run(): void
    {
        $eventos = [
            [
                'nombre' => 'Concierto Rock 2026',
                'descripcion_corta' => 'El mejor concierto de rock del año',
                'descripcion_larga' => 'Disfruta de una noche inolvidable con las mejores bandas de rock internacional. Un espectáculo único que no te puedes perder.',
                'poster_url' => 'https://via.placeholder.com/800x600?text=Concierto+Rock',
                'fecha' => '2026-06-15',
                'hora' => '20:00',
            ],
            [
                'nombre' => 'Final Copa del Rey',
                'descripcion_corta' => 'Gran final de la Copa del Rey',
                'descripcion_larga' => 'Vive la emoción de la final de la Copa del Rey en directo. Los dos mejores equipos se enfrentan por el título.',
                'poster_url' => 'https://via.placeholder.com/800x600?text=Final+Copa',
                'fecha' => '2026-07-20',
                'hora' => '21:00',
            ],
            [
                'nombre' => 'Festival Electrónica',
                'descripcion_corta' => 'Los mejores DJs del mundo',
                'descripcion_larga' => 'Festival de música electrónica con los DJs más reconocidos a nivel mundial. Una experiencia única de sonido y luces.',
                'poster_url' => 'https://via.placeholder.com/800x600?text=Festival+Electronica',
                'fecha' => '2026-08-10',
                'hora' => '19:00',
            ],
            [
                'nombre' => 'Obra de Teatro Clásico',
                'descripcion_corta' => 'Teatro clásico español',
                'descripcion_larga' => 'Representación de una obra clásica del teatro español con los mejores actores del país.',
                'poster_url' => 'https://via.placeholder.com/800x600?text=Teatro',
                'fecha' => '2026-09-05',
                'hora' => '18:30',
            ],
        ];

        foreach ($eventos as $evento) {
            Evento::create($evento);
        }

        $this->command->info('✅ Eventos creados: ' . count($eventos));
    }
}