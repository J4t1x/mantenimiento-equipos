<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\RetiroUso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RetiroUso>
 */
class RetiroUsoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipo_id' => Equipo::factory(),
            'fecha_retiro' => now()->toDateString(),
            'motivo' => 'MP reprogramada no realizada dentro de 30 días',
            'documento_retiro' => fake()->numerify('Memo N° ###'),
        ];
    }
}
