<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // User::factory(10)->create();

        // firstOrCreate en vez de create(): permite volver a correr el seeder (ej. tras agregar un
        // usuario nuevo a esta lista) sin chocar con el email único de los que ya existen.
        $usuarios = [
            ['name' => 'Test User', 'email' => 'test@example.com'],
            ['name' => 'Administrador SSA', 'email' => 'admin@saludaysen.cl'],
        ];

        foreach ($usuarios as $datos) {
            User::query()->firstOrCreate(
                ['email' => $datos['email']],
                User::factory()->raw(['name' => $datos['name'], 'email' => $datos['email']]),
            )->assignRole('super_admin');
        }
    }
}
