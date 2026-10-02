<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Actions\ObtenerInformeCriticosAction;
use App\Actions\RegistrarRetiroUsoAction;
use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Enums\Periodo;
use App\Exceptions\RetiroUsoInvalidoException;
use App\Filament\Resources\EjecucionesMensuales\Pages\EditEjecucionMensual;
use App\Filament\Resources\Equipos\Pages\ListEquipos;
use App\Filament\Resources\Equipos\Pages\ViewEquipo;
use App\Filament\Widgets\CatastroAlertasWidget;
use App\Models\EjecucionMensual;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Módulo 16 (cobertura de la Res. Ex. 1341/2017): RF-71 críticos sin plan del año (§7.2), RF-73
 * retiro de uso con evidencia escrita (§7.4.iii) y RF-74 documento formal de justificación
 * (§7.4.ii).
 */
class CoberturaNormaTest extends TestCase
{
    use RefreshDatabase;

    private const ANIO = 2026;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(self::ANIO, 9, 15));
    }

    /**
     * @return array<int, mixed>
     */
    private function statsDelWidget(): array
    {
        $widget = new CatastroAlertasWidget;

        return (new ReflectionMethod($widget, 'getStats'))->invoke($widget);
    }

    public function test_detecta_criticos_activos_sin_plan_del_anio(): void
    {
        $sinPlan = Equipo::factory()->create();
        $soloAnioAnterior = Equipo::factory()->create();
        PlanMantenimiento::factory()->for($soloAnioAnterior)->create(['anio' => self::ANIO - 1]);
        PlanMantenimiento::factory()->create(['anio' => self::ANIO]);
        Equipo::factory()->relevante()->create();
        Equipo::factory()->create(['activo' => false]);

        $this->assertEqualsCanonicalizing(
            [$sinPlan->id, $soloAnioAnterior->id],
            Equipo::query()->criticosSinPlan()->pluck('id')->all(),
        );
        $this->assertSame(2, app(ObtenerInformeCriticosAction::class)->execute(self::ANIO, Periodo::Anual)['resumen']['criticos_sin_plan']);
    }

    public function test_el_escritorio_alerta_criticos_sin_plan_y_enlaza_a_equipos_filtrados(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $sinPlan = Equipo::factory()->create();
        $conPlan = PlanMantenimiento::factory()->create(['anio' => self::ANIO])->equipo;

        $alerta = $this->statsDelWidget()[3];

        $this->assertSame(1, $alerta->getValue());
        $this->assertStringContainsString('filters[criticos_sin_plan][isActive]=1', urldecode($alerta->getUrl()));

        Livewire::test(ListEquipos::class)
            ->filterTable('criticos_sin_plan')
            ->assertCanSeeTableRecords([$sinPlan])
            ->assertCanNotSeeTableRecords([$conPlan]);
    }

    public function test_retirar_de_uso_deja_el_equipo_inactivo_y_fuera_de_la_alerta_de_retiro(): void
    {
        $plan = PlanMantenimiento::factory()->create(['anio' => self::ANIO, 'frecuencia_anual' => FrecuenciaAnual::Cuatro]);
        $plan->ejecucionesMensuales()->where('mes', 6)->first()->update(['estado' => EstadoEjecucion::Reprogramado, 'observaciones' => 'Sin repuesto']);
        $this->assertSame(1, EjecucionMensual::query()->reprogramacionesVencidas()->count());

        $retiro = app(RegistrarRetiroUsoAction::class)->retirar($plan->equipo, CarbonImmutable::parse('2026-08-05'), 'MP de junio no realizada', 'Memo N° 45');

        $this->assertFalse($plan->equipo->fresh()->activo);
        $this->assertTrue($retiro->is($plan->equipo->retiroUsoAbierto()));
        $this->assertSame(0, EjecucionMensual::query()->reprogramacionesVencidas()->count());

        $fila = app(ObtenerInformeCriticosAction::class)->execute(self::ANIO, Periodo::Anual)['reprogramaciones']->sole();
        $this->assertSame('05-08-2026', $fila['retiro_uso']);
    }

    public function test_no_retira_dos_veces_y_el_reingreso_cierra_el_retiro(): void
    {
        $equipo = Equipo::factory()->create();
        $accion = app(RegistrarRetiroUsoAction::class);
        $accion->retirar($equipo, now(), 'Falla sin reparar', 'Memo N° 1');

        try {
            $accion->retirar($equipo, now(), 'Otra vez', 'Memo N° 2');
            $this->fail('Se permitió retirar un equipo ya retirado.');
        } catch (RetiroUsoInvalidoException) {
        }

        $accion->reingresar($equipo, now(), 'Memo N° 3');

        $this->assertTrue($equipo->fresh()->activo);
        $this->assertNull($equipo->retiroUsoAbierto());
        $this->assertSame('Memo N° 3', $equipo->retirosUso()->sole()->documento_reingreso);

        $this->expectException(RetiroUsoInvalidoException::class);
        $accion->reingresar($equipo, now(), 'Memo N° 4');
    }

    public function test_la_ficha_del_equipo_registra_el_retiro_con_su_evidencia(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $equipo = Equipo::factory()->create();

        Livewire::test(ViewEquipo::class, ['record' => $equipo->getRouteKey()])
            ->assertActionHidden('registrarReingreso')
            ->callAction('retirarDeUso', data: ['fecha' => '2026-09-10', 'motivo' => 'MP reprogramada no realizada', 'documento' => 'Memo N° 7'])
            ->assertHasNoActionErrors();

        $retiro = $equipo->retirosUso()->sole();
        $this->assertSame('Memo N° 7', $retiro->documento_retiro);
        $this->assertFalse($equipo->fresh()->activo);

        Livewire::test(ViewEquipo::class, ['record' => $equipo->getRouteKey()])
            ->assertActionHidden('retirarDeUso')
            ->assertActionVisible('registrarReingreso');
    }

    public function test_sin_permiso_de_edicion_no_se_puede_retirar(): void
    {
        $this->actuarComo('Jefatura');
        $equipo = Equipo::factory()->create();

        Livewire::test(ViewEquipo::class, ['record' => $equipo->getRouteKey()])
            ->assertActionHidden('retirarDeUso');
    }

    public function test_la_reprogramacion_registra_el_documento_de_justificacion(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $plan = PlanMantenimiento::factory()->create(['anio' => self::ANIO, 'frecuencia_anual' => FrecuenciaAnual::Cuatro]);
        $ejecucion = $plan->ejecucionesMensuales()->where('mes', 9)->firstOrFail();

        Livewire::test(EditEjecucionMensual::class, ['record' => $ejecucion->getRouteKey()])
            ->fillForm(['estado' => EstadoEjecucion::Reprogramado, 'observaciones' => 'Equipo en uso', 'documento_justificacion' => 'Memo N° 88'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Memo N° 88', $ejecucion->fresh()->documento_justificacion);
        $this->assertSame('Memo N° 88', app(ObtenerInformeCriticosAction::class)->execute(self::ANIO, Periodo::Anual)['reprogramaciones']->sole()['documento']);
    }
}
