<?php

namespace Tests\Feature;

use App\Models\Recinto;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * SPEC-ME-011: el seeder no crea usuarios de desarrollo fuera de `local`/`testing`, y los usuarios
 * reales se crean con `app:crear-usuario`.
 */
class PreparacionProduccionTest extends TestCase
{
    use RefreshDatabase;

    private function enEntorno(string $entorno, callable $accion): void
    {
        $anterior = $this->app['env'];
        $this->app['env'] = $entorno;

        try {
            $accion();
        } finally {
            $this->app['env'] = $anterior;
        }
    }

    public function test_en_produccion_el_seeder_crea_roles_y_ningun_usuario(): void
    {
        // Como en el servidor: en producción `db:seed` exige --force.
        $this->enEntorno('production', fn () => $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])
            ->expectsOutputToContain('no se crean usuarios de desarrollo')
            ->assertSuccessful());

        $this->assertSame(0, User::query()->count());
        $this->assertEqualsCanonicalizing(
            ['super_admin', 'Encargado de Mantención', 'Técnico Interno', 'Encargado de Convenios', 'Jefatura'],
            Role::query()->pluck('name')->all(),
        );
    }

    public function test_en_local_el_seeder_crea_los_usuarios_de_desarrollo(): void
    {
        $this->enEntorno('local', fn () => $this->seed(DatabaseSeeder::class));

        $this->assertEqualsCanonicalizing(['test@example.com', 'admin@saludaysen.cl'], User::query()->pluck('email')->all());
        $this->assertTrue(User::query()->where('email', 'admin@saludaysen.cl')->sole()->hasRole('super_admin'));
    }

    public function test_crea_un_usuario_con_rol_recintos_y_contrasena(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $coyhaique = Recinto::factory()->create(['nombre' => 'Hospital Regional Coyhaique']);
        $aysen = Recinto::factory()->create(['nombre' => 'Hospital de Puerto Aysén']);

        $this->artisan('app:crear-usuario', [
            '--nombre' => 'Raúl Aravena Turra',
            '--email' => 'raul.aravena@saludaysen.cl',
            '--rol' => 'Encargado de Mantención',
            '--recinto' => ['Hospital Regional Coyhaique', (string) $aysen->id],
            '--password' => 'clave-segura-2026',
            '--no-interaction' => true,
        ])->expectsOutputToContain('Usuario creado: raul.aravena@saludaysen.cl')->assertSuccessful();

        $usuario = User::query()->where('email', 'raul.aravena@saludaysen.cl')->sole();
        $this->assertTrue($usuario->hasRole('Encargado de Mantención'));
        $this->assertTrue($usuario->activo);
        $this->assertTrue(Hash::check('clave-segura-2026', $usuario->password));
        $this->assertEqualsCanonicalizing([$coyhaique->id, $aysen->id], $usuario->recintos()->pluck('recintos.id')->all());
    }

    public function test_sin_contrasena_ni_interaccion_genera_una_segura_y_la_muestra(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $codigo = Artisan::call('app:crear-usuario', [
            '--nombre' => 'Administrador Producción',
            '--email' => 'admin.prod@saludaysen.cl',
            '--rol' => 'super_admin',
            '--no-interaction' => true,
        ]);
        $contrasena = trim(collect(explode("\n", trim(Artisan::output())))->last());

        $this->assertSame(0, $codigo);
        $this->assertGreaterThanOrEqual(16, mb_strlen($contrasena));
        $this->assertTrue(Hash::check($contrasena, User::query()->where('email', 'admin.prod@saludaysen.cl')->sole()->password));
    }

    public function test_rechaza_correo_repetido_rol_inexistente_y_recinto_inexistente(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        User::factory()->create(['email' => 'repetido@saludaysen.cl']);
        $base = ['--nombre' => 'Persona', '--password' => 'clave-segura-2026', '--no-interaction' => true];

        $this->artisan('app:crear-usuario', [...$base, '--email' => 'repetido@saludaysen.cl', '--rol' => 'Jefatura'])
            ->assertFailed();

        $this->artisan('app:crear-usuario', [...$base, '--email' => 'nuevo@saludaysen.cl', '--rol' => 'Administrador'])
            ->expectsOutputToContain('El rol no existe')
            ->assertFailed();

        $this->artisan('app:crear-usuario', [...$base, '--email' => 'nuevo@saludaysen.cl', '--rol' => 'Jefatura', '--recinto' => ['Hospital Inexistente']])
            ->expectsOutputToContain('El recinto "Hospital Inexistente" no existe')
            ->assertFailed();

        $this->artisan('app:crear-usuario', [...$base, '--email' => 'corta@saludaysen.cl', '--rol' => 'Jefatura', '--password' => 'corta'])
            ->expectsOutputToContain('al menos 12 caracteres')
            ->assertFailed();

        $this->assertSame(1, User::query()->count());
    }

    public function test_pide_los_datos_faltantes_y_valida_la_confirmacion_de_contrasena(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->artisan('app:crear-usuario')
            ->expectsQuestion('Nombre completo', 'Técnico de Prueba')
            ->expectsQuestion('Correo electrónico', 'tecnico@saludaysen.cl')
            ->expectsChoice('Rol', 'Técnico Interno', Role::query()->orderBy('name')->pluck('name')->all())
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'clave-segura-2026')
            ->expectsQuestion('Repite la contraseña', 'otra-clave-2026')
            ->expectsOutputToContain('Las contraseñas no coinciden')
            ->assertFailed();

        $this->assertSame(0, User::query()->count());
    }

    public function test_sin_roles_indica_correr_el_seeder(): void
    {
        $this->artisan('app:crear-usuario', ['--no-interaction' => true])
            ->expectsOutputToContain('db:seed --force')
            ->assertFailed();
    }
}
