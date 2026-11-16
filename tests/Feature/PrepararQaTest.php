<?php

namespace Tests\Feature;

use App\Console\Commands\PrepararQa;
use App\Enums\Criticidad;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use App\Models\Proveedor;
use App\Models\Recinto;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * SPEC-ME-021: `app:preparar-qa` deja el ambiente de pruebas listo para la pauta de aceptación.
 */
class PrepararQaTest extends TestCase
{
    use RefreshDatabase;

    private Recinto $coyhaique;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 11, 16));
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->coyhaique = Recinto::factory()->create(['nombre' => 'Hospital Regional Coyhaique']);
    }

    /**
     * Frecuencia 4: marzo, junio, septiembre y diciembre programados (tres ya pasados en noviembre).
     */
    private function plan(Criticidad $criticidad, bool $enGarantia = false): PlanMantenimiento
    {
        $equipo = Equipo::factory()->create([
            'recinto_id' => $this->coyhaique->id,
            'nombre' => 'Equipo '.Str::random(8),
            'criticidad' => $criticidad,
            'en_garantia' => $enGarantia,
        ]);

        return PlanMantenimiento::factory()->create(['equipo_id' => $equipo->id, 'anio' => 2026]);
    }

    public function test_crea_cuentas_recinto_de_prueba_y_asigna_los_equipos_de_referencia(): void
    {
        $critico = $this->plan(Criticidad::Critico);
        $relevante = $this->plan(Criticidad::Relevante);
        $otro = $this->plan(Criticidad::Relevante);
        $enGarantia = $this->plan(Criticidad::Critico, enGarantia: true);

        $this->artisan('app:preparar-qa', ['--correo-seguridad' => 'pruebas.bitacora@saludaysen.cl'])
            ->expectsOutputToContain('Ambiente de pruebas preparado')
            ->expectsOutputToContain('se muestran una sola vez')
            ->assertSuccessful();

        $tecnico = User::query()->where('email', 'qa.tecnico@prueba.invalid')->sole();
        $this->assertTrue($tecnico->hasRole('Técnico Interno'));
        $this->assertSame([$this->coyhaique->id], $tecnico->recintos()->pluck('recintos.id')->all());
        $this->assertTrue(User::query()->where('email', 'qa.admin@prueba.invalid')->sole()->hasRole('super_admin'));
        $this->assertSame([], User::query()->where('email', 'qa.jefatura@prueba.invalid')->sole()->recintos()->pluck('recintos.id')->all());
        $this->assertTrue(User::query()->where('email', 'pruebas.bitacora@saludaysen.cl')->sole()->hasRole('Jefatura'));

        // A: crítico sin garantía; B: otro asignado; C: sin responsable. El crítico en garantía no se asigna.
        $this->assertSame($tecnico->id, $critico->fresh()->responsable_interno_id);
        $this->assertSame($tecnico->id, $relevante->fresh()->responsable_interno_id);
        $this->assertNull($otro->fresh()->responsable_interno_id);
        $this->assertNull($enGarantia->fresh()->responsable_interno_id);

        $equipoD = Equipo::query()->where('n_inventario', PrepararQa::INVENTARIO_EQUIPO_D)->sole();
        $this->assertSame(PrepararQa::RECINTO_PRUEBA, $equipoD->recinto->nombre);
        $this->assertTrue(Proveedor::query()->where('nombre', PrepararQa::PROVEEDOR_PRUEBA)->exists());
    }

    public function test_se_puede_ejecutar_de_nuevo_sin_duplicar_ni_cambiar_contrasenas(): void
    {
        $critico = $this->plan(Criticidad::Critico);
        $relevante = $this->plan(Criticidad::Relevante);
        $this->plan(Criticidad::Relevante);

        $this->artisan('app:preparar-qa')->assertSuccessful();
        $hash = User::query()->where('email', 'qa.tecnico@prueba.invalid')->sole()->password;

        $this->artisan('app:preparar-qa')
            ->expectsOutputToContain('(sin cambios)')
            ->expectsOutputToContain('quedan No aplica')
            ->assertSuccessful();

        $this->assertSame(6, User::query()->count());
        $this->assertSame(1, Equipo::query()->where('n_inventario', PrepararQa::INVENTARIO_EQUIPO_D)->count());
        $this->assertSame($hash, User::query()->where('email', 'qa.tecnico@prueba.invalid')->sole()->password);
        $this->assertSame(2, PlanMantenimiento::query()->whereNotNull('responsable_interno_id')->count());
        $this->assertNotNull($critico->fresh()->responsable_interno_id);
        $this->assertNotNull($relevante->fresh()->responsable_interno_id);

        $this->artisan('app:preparar-qa', ['--nuevas-contrasenas' => true])->assertSuccessful();
        $this->assertNotSame($hash, User::query()->where('email', 'qa.tecnico@prueba.invalid')->sole()->password);
    }

    public function test_el_equipo_a_no_puede_tener_garantia_vigente(): void
    {
        $this->plan(Criticidad::Critico, enGarantia: true);
        $this->plan(Criticidad::Relevante);
        $this->plan(Criticidad::Relevante);

        $this->artisan('app:preparar-qa')
            ->expectsOutputToContain('No hay un equipo crítico activo sin garantía vigente')
            ->assertFailed();

        $this->assertSame(0, User::query()->count(), 'La transacción se revierte si falta un equipo de referencia.');
    }

    public function test_los_equipos_b_y_c_son_de_otro_tipo_que_el_a(): void
    {
        $critico = $this->plan(Criticidad::Critico);
        $mismoTipo = $this->plan(Criticidad::Relevante);
        $mismoTipo->equipo->update(['nombre' => $critico->equipo->nombre]);
        $b = $this->plan(Criticidad::Relevante);
        $c = $this->plan(Criticidad::Relevante);

        $this->artisan('app:preparar-qa')->assertSuccessful();

        $this->assertNull($mismoTipo->fresh()->responsable_interno_id);
        $this->assertNotNull($b->fresh()->responsable_interno_id);
        $this->assertNull($c->fresh()->responsable_interno_id);
    }

    public function test_no_se_ejecuta_en_produccion(): void
    {
        $this->plan(Criticidad::Critico);
        $this->app['env'] = 'production';

        $this->artisan('app:preparar-qa')
            ->expectsOutputToContain('no se ejecuta con APP_ENV=production')
            ->assertFailed();

        $this->assertSame(0, User::query()->count());
    }

    public function test_exige_el_catastro_importado(): void
    {
        $this->artisan('app:preparar-qa')
            ->expectsOutputToContain('Importa primero su catastro')
            ->assertFailed();
    }
}
