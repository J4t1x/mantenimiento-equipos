<?php

namespace Tests\Feature\Panel;

use App\Enums\Criticidad;
use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Filament\Pages\BitacoraAnual;
use App\Filament\Resources\PlanMantenimientos\Pages\ViewPlanMantenimiento;
use App\Filament\Resources\PlanMantenimientos\RelationManagers\EjecucionesMensualesRelationManager;
use App\Models\EjecucionMensual;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use App\Models\Recinto;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * RF-79 (Módulo 19): vista anual consolidada de la bitácora, y regresión de la grilla por plan
 * (RF-38), que comparte con ella el formulario del mes (`BitacoraMensual`).
 */
class BitacoraAnualTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 10, 15));
    }

    /**
     * Plan con frecuencia 4 (marzo, junio, septiembre y diciembre programados por RN-01) y, si se
     * indica, marzo realizado.
     *
     * @param  array<string, mixed>  $equipo
     */
    private function plan(array $equipo = [], bool $marzoRealizado = false, int $anio = 2026, ?User $responsable = null): PlanMantenimiento
    {
        $plan = PlanMantenimiento::factory()
            ->for(Equipo::factory()->create($equipo))
            ->create(['anio' => $anio, 'frecuencia_anual' => FrecuenciaAnual::Cuatro, 'responsable_interno_id' => $responsable?->id]);

        if ($marzoRealizado) {
            $plan->ejecucionesMensuales()->where('mes', 3)->first()->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => '2026-03-10']);
        }

        return $plan;
    }

    private function mesesProgramados(PlanMantenimiento $plan): array
    {
        return $plan->ejecucionesMensuales()->where('estado', '!=', EstadoEjecucion::SinProgramar)->orderBy('mes')->pluck('mes')->all();
    }

    public function test_muestra_una_fila_por_plan_del_anio_con_sus_12_meses(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $realizado = $this->plan(['nombre' => 'Ventilador A'], marzoRealizado: true);
        $otro = $this->plan(['nombre' => 'Monitor B']);
        $anioAnterior = $this->plan(anio: 2025);
        $mesProgramado = $this->mesesProgramados($otro)[0];

        Livewire::test(BitacoraAnual::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$realizado, $otro])
            ->assertCanNotSeeTableRecords([$anioAnterior])
            ->assertTableColumnStateSet('mes_3', '√', $realizado)
            ->assertTableColumnStateSet("mes_{$mesProgramado}", 'X', $otro)
            ->assertTableColumnStateSet('mes_1', '–', $otro)
            ->filterTable('anio', '2025')
            ->assertCanSeeTableRecords([$anioAnterior])
            ->assertCanNotSeeTableRecords([$realizado, $otro]);
    }

    public function test_filtra_por_criticidad_recinto_y_busqueda(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $recinto = Recinto::factory()->create();
        $critico = $this->plan(['nombre' => 'Ventilador A', 'recinto_id' => $recinto->id]);
        $relevante = $this->plan(['nombre' => 'Monitor B', 'criticidad' => Criticidad::Relevante]);

        Livewire::test(BitacoraAnual::class)
            ->filterTable('criticidad', Criticidad::Relevante->value)
            ->assertCanSeeTableRecords([$relevante])
            ->assertCanNotSeeTableRecords([$critico])
            ->resetTableFilters()
            ->filterTable('recinto', $recinto->id)
            ->assertCanSeeTableRecords([$critico])
            ->assertCanNotSeeTableRecords([$relevante])
            ->resetTableFilters()
            ->searchTable('Monitor')
            ->assertCanSeeTableRecords([$relevante])
            ->assertCanNotSeeTableRecords([$critico]);
    }

    public function test_respeta_el_alcance_por_recinto(): void
    {
        [$propio, $ajeno] = Recinto::factory()->count(2)->create();
        $planPropio = $this->plan(['recinto_id' => $propio->id]);
        $planAjeno = $this->plan(['recinto_id' => $ajeno->id]);
        $usuario = User::factory()->create();
        $usuario->recintos()->attach($propio->id);
        $this->actuarComo('Encargado de Mantención', $usuario);

        Livewire::test(BitacoraAnual::class)
            ->assertCanSeeTableRecords([$planPropio])
            ->assertCanNotSeeTableRecords([$planAjeno]);
    }

    public function test_registra_un_mes_desde_la_celda(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $plan = $this->plan();
        $mes = $this->mesesProgramados($plan)[0];

        Livewire::test(BitacoraAnual::class)
            ->callAction(TestAction::make("editarMes{$mes}")->table($plan), [
                'estado' => EstadoEjecucion::Realizado->value,
                'fecha_real' => '2026-03-12',
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(EstadoEjecucion::Realizado, $plan->ejecucionesMensuales()->where('mes', $mes)->first()->estado);
    }

    public function test_aplica_rn02_y_la_causa_de_reprogramacion(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $plan = $this->plan();
        $mesProgramado = $this->mesesProgramados($plan)[0];

        Livewire::test(BitacoraAnual::class)
            ->callAction(TestAction::make('editarMes1')->table($plan), ['estado' => EstadoEjecucion::Realizado->value])
            ->assertHasFormErrors(['estado']);

        Livewire::test(BitacoraAnual::class)
            ->callAction(TestAction::make("editarMes{$mesProgramado}")->table($plan), ['estado' => EstadoEjecucion::Reprogramado->value, 'observaciones' => null])
            ->assertHasFormErrors(['observaciones' => 'required']);

        $this->assertSame(EstadoEjecucion::SinProgramar, $plan->ejecucionesMensuales()->where('mes', 1)->first()->estado);
    }

    public function test_el_tecnico_solo_registra_en_sus_planes_y_jefatura_solo_lee(): void
    {
        $tecnico = User::factory()->create();
        $asignado = $this->plan(responsable: $tecnico);
        $ajeno = $this->plan();
        $this->actuarComo('Técnico Interno', $tecnico);

        Livewire::test(BitacoraAnual::class)
            ->assertCanSeeTableRecords([$asignado, $ajeno])
            ->assertActionVisible(TestAction::make('editarMes3')->table($asignado))
            ->assertActionHidden(TestAction::make('editarMes3')->table($ajeno));

        $this->actuarComo('Jefatura');

        Livewire::test(BitacoraAnual::class)
            ->assertCanSeeTableRecords([$asignado])
            ->assertActionHidden(TestAction::make('editarMes3')->table($asignado));
    }

    public function test_el_pie_resume_realizadas_sobre_programadas_por_mes(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $this->plan(marzoRealizado: true);
        $otro = $this->plan();
        $programadosMarzo = EjecucionMensual::query()->where('mes', 3)->where('estado', '!=', EstadoEjecucion::SinProgramar)->count();

        $pagina = Livewire::test(BitacoraAnual::class)->instance();
        $resumen = (new \ReflectionMethod($pagina, 'resumenDelMes'))->invoke(
            $pagina,
            PlanMantenimiento::query()->where('anio', 2026)->toBase(),
            3,
        );

        $this->assertSame("1 / {$programadosMarzo}", $resumen);
        $this->assertNotNull($otro);
    }

    public function test_acceso_por_permiso(): void
    {
        $this->actuarComo('Técnico Interno');
        $this->get(BitacoraAnual::getUrl())->assertOk();

        $this->actuarComo('Encargado de Convenios');
        $this->get(BitacoraAnual::getUrl())->assertForbidden();
    }

    public function test_la_grilla_del_plan_sigue_registrando_con_el_formulario_compartido(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $plan = $this->plan();
        $mes = $this->mesesProgramados($plan)[0];

        $grilla = fn () => Livewire::test(EjecucionesMensualesRelationManager::class, ['ownerRecord' => $plan, 'pageClass' => ViewPlanMantenimiento::class]);

        $grilla()
            ->assertOk()
            ->assertSee('Bitácora mensual — 2026')
            ->callAction(TestAction::make('editarMes')->arguments(['mes' => 1]), ['estado' => EstadoEjecucion::Realizado->value])
            ->assertHasFormErrors(['estado']);

        $grilla()
            ->callAction(TestAction::make('editarMes')->arguments(['mes' => $mes]), ['estado' => EstadoEjecucion::Realizado->value, 'fecha_real' => '2026-03-12'])
            ->assertHasNoFormErrors();

        $this->assertSame(EstadoEjecucion::Realizado, $plan->ejecucionesMensuales()->where('mes', $mes)->first()->estado);
    }
}
