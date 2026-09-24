<?php

namespace Database\Factories;

use App\Models\Convenio;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Convenio>
 */
class ConvenioFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proveedor_id' => Proveedor::factory(),
            'nombre' => 'Convenio '.fake()->words(2, true),
            'n_resolucion' => fake()->numerify('####'),
            'fecha_resolucion' => now()->startOfYear(),
            'fecha_expiracion' => now()->endOfYear(),
            'monto_anual' => 1_000_000,
            'activo' => true,
        ];
    }
}
