<?php

namespace Database\Factories;

use App\Enums\Criticidad;
use App\Enums\EstadoEquipo;
use App\Enums\Propiedad;
use App\Models\ClaseEquipo;
use App\Models\Equipo;
use App\Models\Recinto;
use App\Models\ServicioClinico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipo>
 */
class EquipoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recinto_id' => Recinto::factory(),
            'servicio_clinico_id' => ServicioClinico::factory(),
            'clase_id' => ClaseEquipo::factory(),
            'nombre' => fake()->randomElement(['Monitor multiparámetro', 'Ventilador mecánico', 'Desfibrilador', 'Bomba de infusión']),
            'n_inventario' => fake()->unique()->numerify('INV-#####'),
            'propiedad' => Propiedad::Propio,
            'estado' => EstadoEquipo::Bueno,
            'criticidad' => Criticidad::Critico,
            'bajo_plan_mp' => true,
            'activo' => true,
        ];
    }
}
