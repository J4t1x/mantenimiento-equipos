<?php

namespace Database\Factories;

use App\Models\Recinto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recinto>
 */
class RecintoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Hospital '.fake()->unique()->city(),
            'activo' => true,
        ];
    }
}
