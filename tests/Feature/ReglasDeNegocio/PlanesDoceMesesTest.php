<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Filament\Resources\PlanMantenimientos\Pages\ListPlanMantenimientos;
use App\Filament\Resources\PlanMantenimientos\Tables\PlanMantenimientosTable;
use App\Filament\Widgets\CatastroAlertasWidget;
use App\Models\EjecucionMensual;
use App\Models\PlanMantenimiento;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * SPEC-ME-016 (RN-01): todo plan tiene sus 12 meses; el comando `app:completar-meses-planes`
 * completa los planes cargados antes con menos registros, y el Escritorio alerta de los planes
 * cuyos meses programados no coinciden con la frecuencia.
 */
class PlanesDoceMesesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 10, 15));
    }

    /**
     * Plan como lo dejaba el importador anterior: solo los meses marcados en la planilla.
     *
     * @param  array<int, int>  $mesesConservados
     */
    private function planIncompleto(array $mesesConservados, FrecuenciaAnual $frecuencia = FrecuenciaAnual::Cuatro): PlanMantenimiento
    {
        $plan = PlanMantenimiento::factory()->create(['frecuencia_anual' => $frecuencia]);
        $plan->ejecucionesMensuales()->whereNotIn('mes', $mesesConservados)->delete();

        return $plan;
    }

    private function alerta(): ?Stat
    {
        $widget = new CatastroAlertasWidget;
        $stats = (new ReflectionMethod($widget, 'getStats'))->invoke($widget);

        return collect($stats)->first(fn (Stat $stat): bool => $stat->getLabel() === 'Planes con meses inconsistentes');
    }

    public function test_el_importador_deja_cada_plan_con_sus_12_meses(): void
    {
        $this->artisan('app:importar-catastro-coyhaique')->assertSuccessful();

        $this->assertSame(469 * 12, EjecucionMensual::count());
        $this->assertSame(0, PlanMantenimiento::query()->has('ejecucionesMensuales', '<>', 12)->count());
    }

    public function test_el_comando_sin_aplicar_no_cambia_nada(): void
    {
        $this->planIncompleto([3, 6, 9, 12]);

        $this->artisan('app:completar-meses-planes')
            ->expectsOutputToContain('Planes por completar: 1 (8 meses faltantes)')
            ->assertSuccessful();

        $this->assertSame(4, EjecucionMensual::count());
    }

    public function test_el_comando_completa_los_meses_sin_tocar_las_marcas_y_es_idempotente(): void
    {
        $plan = $this->planIncompleto([3, 6, 9, 12]);
        $plan->ejecucionesMensuales()->where('mes', 3)->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => '2026-03-10']);
        PlanMantenimiento::factory()->create();

        $this->artisan('app:completar-meses-planes', ['--aplicar' => true])
            ->expectsOutputToContain('Planes completados: 1 (8 meses agregados como Sin programar)')
            ->assertSuccessful();

        $meses = $plan->ejecucionesMensuales()->orderBy('mes')->get();
        $this->assertSame(range(1, 12), $meses->pluck('mes')->all());
        $this->assertSame(EstadoEjecucion::Realizado, $meses->firstWhere('mes', 3)->estado);
        $this->assertSame(3, $meses->where('estado', EstadoEjecucion::Programado)->count());
        $this->assertSame(8, $meses->where('estado', EstadoEjecucion::SinProgramar)->count());

        $this->artisan('app:completar-meses-planes', ['--aplicar' => true])
            ->expectsOutputToContain('Planes completados: 0 (0 meses agregados')
            ->assertSuccessful();
        $this->assertSame(24, EjecucionMensual::count());
    }

    public function test_los_scopes_detectan_planes_inconsistentes_y_sin_meses(): void
    {
        PlanMantenimiento::factory()->create();
        $conDosMeses = $this->planIncompleto([6, 12]);
        $sinMeses = $this->planIncompleto([]);
        $inactivo = $this->planIncompleto([12]);
        $inactivo->equipo->update(['activo' => false]);

        $this->assertEqualsCanonicalizing([$conDosMeses->id, $sinMeses->id], PlanMantenimiento::query()->conMesesInconsistentes()->pluck('id')->all());
        $this->assertSame([$sinMeses->id], PlanMantenimiento::query()->sinMesesProgramados()->pluck('id')->all());
    }

    public function test_el_comando_informa_los_planes_inconsistentes_sin_modificarlos(): void
    {
        $this->planIncompleto([]);

        $this->artisan('app:completar-meses-planes', ['--aplicar' => true])
            ->expectsOutputToContain('Planes con meses programados distintos de la frecuencia: 1')
            ->assertSuccessful();

        $this->assertSame(0, EjecucionMensual::where('estado', '!=', EstadoEjecucion::SinProgramar)->count());
    }

    public function test_la_alerta_del_escritorio_cuenta_los_inconsistentes_y_enlaza_al_listado_filtrado(): void
    {
        $this->actuarComo('Encargado de Mantención');
        PlanMantenimiento::factory()->create();
        $conDosMeses = $this->planIncompleto([6, 12]);
        $sinMeses = $this->planIncompleto([]);

        $alerta = $this->alerta();
        $this->assertSame(2, $alerta->getValue());
        $this->assertStringContainsString('1 sin ningún mes', $alerta->getDescription());
        $this->assertStringContainsString(PlanMantenimientosTable::FILTRO_MESES_INCONSISTENTES, urldecode($alerta->getUrl()));

        Livewire::test(ListPlanMantenimientos::class)
            ->filterTable(PlanMantenimientosTable::FILTRO_MESES_INCONSISTENTES)
            ->assertCanSeeTableRecords([$conDosMeses, $sinMeses])
            ->assertCountTableRecords(2);
    }
}
