<?php

namespace Database\Factories;

use App\Models\ProgramaAnualMantenimiento;
use App\Models\Recinto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramaAnualMantenimiento>
 */
class ProgramaAnualMantenimientoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recinto_id' => Recinto::factory(),
            'anio' => now()->year,
            'fecha_definicion' => now()->setDate(now()->year, 3, 15)->toDateString(),
            'fecha_validacion' => now()->setDate(now()->year, 3, 28)->toDateString(),
            'validado_por_nombre' => fake()->name(),
            'validado_por_cargo' => 'Director(a) del establecimiento',
            'documento_validacion' => fake()->numerify('Res. Ex. N° ####'),
        ];
    }

    public function sinValidar(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_validacion' => null,
            'validado_por_nombre' => null,
            'validado_por_cargo' => null,
            'documento_validacion' => null,
        ]);
    }
}
