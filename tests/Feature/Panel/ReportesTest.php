<?php

namespace Tests\Feature\Panel;

use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Enums\Periodo;
use App\Exports\CatastroPlanExport;
use App\Exports\CumplimientoExport;
use App\Exports\DetalleGastoExport;
use App\Filament\Pages\Reportes;
use App\Models\Equipo;
use App\Models\MantenimientoCorrectivo;
use App\Models\PlanMantenimiento;
use App\Models\Recinto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Reportes (RF-31 a RF-33, RF-54, RF-62): un único alcance para los tres reportes, resumen que se
 * recalcula al filtrar y descarga directa a Excel.
 */
class ReportesTest extends TestCase
{
    use RefreshDatabase;

    private Recinto $recinto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 9, 15));
        $this->actuarComo('Encargado de Mantención');

        $this->recinto = Recinto::factory()->create(['nombre' => 'Hospital Regional Coyhaique']);
        $propio = PlanMantenimiento::factory()->for(Equipo::factory()->for($this->recinto))->create(['anio' => 2026, 'frecuencia_anual' => FrecuenciaAnual::Dos]);
        $propio->ejecucionesMensuales()->where('mes', 6)->first()->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => now()]);
        PlanMantenimiento::factory()->create(['anio' => 2026, 'frecuencia_anual' => FrecuenciaAnual::Dos]);

        MantenimientoCorrectivo::factory()->for($propio->equipo)->create(['fecha' => '2026-03-01', 'costo' => 250_000]);
        MantenimientoCorrectivo::factory()->create(['fecha' => '2026-03-01', 'costo' => 100_000]);
    }

    public function test_la_pagina_no_expone_referencias_internas(): void
    {
        $html = $this->get(Reportes::getUrl())->assertOk()->getContent();

        foreach (['RF-', 'RN-', 'BRIEF', 'pregunta abierta'] as $interno) {
            $this->assertStringNotContainsString($interno, $html);
        }
    }

    public function test_el_resumen_se_recalcula_al_cambiar_el_alcance(): void
    {
        Livewire::test(Reportes::class)
            ->assertSet('filtros.anio', 2026)
            ->assertSee('equipos con plan de MP en 2026')
            ->assertSee('1 de 4 programadas en 2026')
            ->assertSee('Correctivo $350.000')
            ->set('filtros.recinto_id', $this->recinto->id)
            ->assertSee('equipo con plan de MP en 2026')
            ->assertSee('1 de 2 programadas en 2026')
            ->assertSee('Correctivo $250.000')
            ->set('filtros.anio', '20')
            ->assertSee('Ingrese un año válido');
    }

    public function test_descarga_directa_con_el_alcance_elegido(): void
    {
        Excel::fake();

        Livewire::test(Reportes::class)->callAction('descargarCatastroPlan');
        Excel::assertDownloaded('catastro-plan-2026.xlsx', fn (CatastroPlanExport $export): bool => $export->collection()->count() === 2);

        Livewire::test(Reportes::class)
            ->set('filtros.recinto_id', $this->recinto->id)
            ->callAction('descargarCatastroPlan');
        Excel::assertDownloaded('catastro-plan-2026-hospital-regional-coyhaique.xlsx', fn (CatastroPlanExport $export): bool => $export->collection()->count() === 1);

        Livewire::test(Reportes::class)->callAction('descargarCumplimiento');
        Excel::assertDownloaded('cumplimiento-mp-2026.xlsx', fn (CumplimientoExport $export): bool => true);

        Livewire::test(Reportes::class)->callAction('descargarDetalleGasto');
        Excel::assertDownloaded('detalle-gasto-2026.xlsx', fn (DetalleGastoExport $export): bool => true);
    }

    /**
     * RF-67: el período acota el cumplimiento y el gasto, sus resúmenes y sus archivos; el catastro
     * + plan sigue siendo anual.
     */
    public function test_el_periodo_acota_cumplimiento_y_gasto(): void
    {
        Excel::fake();

        Livewire::test(Reportes::class)
            ->assertSet('filtros.periodo', Periodo::Anual->value)
            ->set('filtros.periodo', Periodo::PrimerSemestre->value)
            ->assertSee('1 de 2 programadas en el 1er semestre de 2026')
            ->assertSee('Correctivo $350.000')
            ->set('filtros.periodo', Periodo::SegundoTrimestre->value)
            ->assertSee('Correctivo $0')
            ->assertSee('equipos con plan de MP en 2026');

        Livewire::test(Reportes::class)
            ->set('filtros.periodo', Periodo::PrimerSemestre->value)
            ->callAction('descargarCumplimiento')
            ->callAction('descargarCatastroPlan');

        Excel::assertDownloaded('cumplimiento-mp-2026-s1.xlsx', fn (CumplimientoExport $export): bool => $export->array()[0][1] === 2
            && $export->title() === 'Cumplimiento MP 2026 S1');
        Excel::assertDownloaded('catastro-plan-2026.xlsx', fn (CatastroPlanExport $export): bool => $export->collection()->count() === 2);
    }

    public function test_sin_anio_no_descarga(): void
    {
        Excel::fake();

        Livewire::test(Reportes::class)
            ->set('filtros.anio', null)
            ->callAction('descargarCatastroPlan')
            ->assertHasErrors(['filtros.anio']);
    }
}
