<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Crea un usuario con uno de los roles de negocio (o `super_admin`), usando los permisos reales
     * de `RolesAndPermissionsSeeder`, y lo deja autenticado en el panel. Acepta un usuario ya creado
     * para poder preparar datos a su nombre antes de autenticarlo.
     */
    protected function actuarComo(string $rol, ?User $usuario = null): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $usuario ??= User::factory()->create();
        $usuario->assignRole($rol);

        $this->actingAs($usuario);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $usuario;
    }
}
