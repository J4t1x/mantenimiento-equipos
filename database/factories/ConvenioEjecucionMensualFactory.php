<?php

namespace Database\Factories;

use App\Models\Convenio;
use App\Models\ConvenioEjecucionMensual;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConvenioEjecucionMensual>
 */
class ConvenioEjecucionMensualFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'convenio_id' => Convenio::factory(),
            'anio' => now()->year,
            'mes' => now()->month,
            'n_orden_compra' => fake()->numerify('OC-#####'),
            'monto' => 100_000,
        ];
    }
}
