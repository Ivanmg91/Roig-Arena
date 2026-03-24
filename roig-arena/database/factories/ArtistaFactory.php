<?php

namespace Database\Factories;

use App\Models\Evento;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArtistaFactory extends Factory
{
    protected $model = Artista::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->sentence(3),
            'evento_id' => Evento::factory(),
            'descripcion' => $this->faker->paragraphs(3, true),
            'imagen_url' => $this->faker->imageUrl(640, 480, 'artists', true),
        ];
    }
}