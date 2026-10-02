<?php

namespace Database\Factories;

use App\Models\PresupuestoMantenimiento;
use App\Models\Recinto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PresupuestoMantenimiento>
 */
class PresupuestoMantenimientoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recinto_id' => Recinto::factory(),
            'anio' => now()->year,
            'gasto_programado_mp' => 450_000_000,
            'gasto_programado_mc' => 400_000_000,
        ];
    }
}
