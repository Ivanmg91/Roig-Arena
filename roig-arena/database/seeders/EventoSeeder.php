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
                'descripcion_larga' => 'Disfruta de una noche inolvidable con la mejor banda de rock española. Un espectáculo único que no te puedes perder.',
                'poster_url' => 'https://www.tuposter.com/pub/media/catalog/product/cache/71d04d62b2100522587d43c930e8a36b/f/i/file_80_60.jpg',
                'poster_ancho_url' => 'https://mariskalrock.com/wp-content/uploads/2024/01/extremoduro-agila-fb.jpg',
                'fecha' => '2026-06-15',
                'hora' => '20:00',
            ],
            [
                'nombre' => 'Final Copa del Rey',
                'descripcion_corta' => 'Gran final de la Copa del Rey',
                'descripcion_larga' => 'Vive la emoción de la final de la Copa del Rey en directo. Los dos mejores equipos se enfrentan por el título.',
                'poster_url' => 'https://d1csarkz8obe9u.cloudfront.net/posterpreviews/poster-final-copa-del-rey-2025-design-template-bd71f2c0f602aabc6c9a54965222baf0_screen.jpg?ts=1744218161',
                'poster_ancho_url' => 'https://d1csarkz8obe9u.cloudfront.net/posterpreviews/barcelona-vs-real-madrid-copa-del-rey-design-template-864b7d8a30e5f5ccece1644a9f3139fe_screen.jpg?ts=1745262065',
                'fecha' => '2026-07-20',
                'hora' => '21:00',
            ],
            [
                'nombre' => 'Festival Electrónica',
                'descripcion_corta' => 'Los mejores DJs del mundo',
                'descripcion_larga' => 'Festival de música electrónica con los DJs más reconocidos a nivel mundial. Una experiencia única de sonido y luces.',
                'poster_url' => 'https://d1csarkz8obe9u.cloudfront.net/posterpreviews/electronic-music-festival-poster-design-template-14ff21e7eb2309a793e750889f210079_screen.jpg?ts=1636995776',
                'poster_ancho_url' => 'https://img.freepik.com/vector-gratis/set-posters-festival-musica-electronica_52683-20866.jpg',
                'fecha' => '2026-08-10',
                'hora' => '19:00',
            ],
            [
                'nombre' => 'Obra de Teatro Clásico',
                'descripcion_corta' => 'Teatro clásico español',
                'descripcion_larga' => 'Representación de una obra clásica del teatro español con los mejores actores del país.',
                'poster_url' => 'https://www.comunidad.madrid/docs/styles/free_crop_992w_x1/public/assets/2024/09/17/la_dama_boba_72.jpg?itok=yRaHM_Dv',
                'poster_ancho_url' => 'https://www.apropacultura.org/sites/default/files/event/ldb_web_cartel_1.png',
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
