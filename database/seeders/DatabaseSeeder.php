<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Roles y permisos en todos los entornos. Los usuarios de desarrollo (contraseña `password`)
     * solo en `local` y `testing` (SPEC-ME-011): en cualquier otro entorno, el primer usuario se crea
     * con `php artisan app:crear-usuario` (ver docs/DESPLIEGUE.md).
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Entorno '.app()->environment().': no se crean usuarios de desarrollo. Crea el primer administrador con `php artisan app:crear-usuario --rol=super_admin`.');

            return;
        }

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
