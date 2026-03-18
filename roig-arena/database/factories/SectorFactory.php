<?php

namespace Database\Factories;

use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectorFactory extends Factory
{
    protected $model = Sector::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Sector ' . $this->faker->numberBetween(101, 323),
            'descripcion' => $this->faker->optional()->sentence(),
            'activo' => true,
        ];
    }
}