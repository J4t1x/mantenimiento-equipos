<?php

namespace Database\Factories;

use App\Enums\FrecuenciaAnual;
use App\Enums\TipoMantenimiento;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanMantenimiento>
 */
class PlanMantenimientoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipo_id' => Equipo::factory(),
            'anio' => now()->year,
            'frecuencia_anual' => FrecuenciaAnual::Cuatro,
            'tipo_mantenimiento' => TipoMantenimiento::Interno,
            'costo_anual_referencia' => null,
        ];
    }
}
