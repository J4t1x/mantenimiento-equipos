<?php

namespace Database\Factories;

use App\Models\ServicioClinico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicioClinico>
 */
class ServicioClinicoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->randomElement(['UCI', 'Pabellón', 'Urgencia', 'Pediatría', 'Neonatología', 'Imagenología', 'Laboratorio', 'Medicina', 'Cirugía', 'Diálisis']).' '.fake()->unique()->numberBetween(1, 9999),
            'activo' => true,
        ];
    }
}
