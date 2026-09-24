<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\MantenimientoCorrectivo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MantenimientoCorrectivo>
 */
class MantenimientoCorrectivoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipo_id' => Equipo::factory(),
            'fecha' => now(),
            'falla_descripcion' => fake()->sentence(),
            'costo' => 100_000,
        ];
    }
}
