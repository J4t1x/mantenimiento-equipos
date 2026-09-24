<?php

namespace Database\Factories;

use App\Models\ClaseEquipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClaseEquipo>
 */
class ClaseEquipoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word().' '.fake()->numberBetween(1, 9999),
            'clase_padre_id' => null,
            'activo' => true,
        ];
    }
}
