<?php

namespace Tests\Feature\Panel;

use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Enums\Periodo;
use App\Filament\Pages\Escritorio;
use App\Filament\Widgets\AlertasConveniosWidget;
use App\Filament\Widgets\CatastroAlertasWidget;
use App\Filament\Widgets\CumplimientoMpMensualWidget;
use App\Filament\Widgets\CumplimientoMpWidget;
use App\Filament\Widgets\IndicadoresGeneralesWidget;
use App\Models\Convenio;
use App\Models\ConvenioEjecucionMensual;
use App\Models\Equipo;
use App\Models\MantenimientoCorrectivo;
use App\Models\PlanMantenimiento;
use App\Models\Recinto;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportReactiveProps\CannotMutateReactivePropException;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * RF-21 (cierre): el Escritorio tiene su propio selector de año y período (año, semestre, trimestre
 * o mes), sin el error 500 de `HasFiltersForm`. Desde SPEC-ME-002 el período llega a todos los
 * widgets que dependen del año, no solo al indicador de cumplimiento.
 */
class EscritorioPeriodoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 9, 15));

        $plan = PlanMantenimiento::factory()->create(['anio' => 2026, 'frecuencia_anual' => FrecuenciaAnual::Cuatro]);
        $plan->ejecucionesMensuales()->where('mes', 3)->first()->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => now()]);
    }

    /**
     * @return array<int, mixed>
     */
    private function stats(array $propiedades, string $widget = CumplimientoMpWidget::class): array
    {
        $instancia = Livewire::test($widget, $propiedades)->instance();

        return (new ReflectionMethod($instancia, 'getStats'))->invoke($instancia);
    }

    private function stat(string $widget, array $propiedades, string $etiqueta): ?Stat
    {
        return collect($this->stats($propiedades, $widget))
            ->first(fn (Stat $stat): bool => $stat->getLabel() === $etiqueta);
    }

    public function test_el_escritorio_con_selector_de_periodo_se_renderiza_sin_error(): void
    {
        $this->actuarComo('Encargado de Mantención');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Período del Escritorio');
    }

    public function test_el_encargado_de_convenios_tambien_ve_el_selector(): void
    {
        $this->actuarComo('Encargado de Convenios');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Período del Escritorio');
    }

    public function test_la_pagina_entrega_anio_y_periodo_como_valores_escalares(): void
    {
        $this->actuarComo('Encargado de Mantención');

        $pagina = Livewire::test(Escritorio::class)
            ->set('periodoEscritorio.anio', 2025)
            ->set('periodoEscritorio.periodo', Periodo::PrimerTrimestre->value)
            ->instance();

        $this->assertSame(['anio' => 2025, 'periodo' => 't1'], $pagina->getWidgetData());
    }

    public function test_el_widget_calcula_el_periodo_recibido(): void
    {
        $this->actuarComo('Encargado de Mantención');

        $this->assertSame('25%', $this->stats([])[0]->getValue());
        $this->assertSame('50%', $this->stats(['anio' => 2026, 'periodo' => Periodo::PrimerSemestre->value])[0]->getValue());
        $this->assertSame('100%', $this->stats(['anio' => 2026, 'periodo' => Periodo::Marzo->value])[0]->getValue());
        $this->assertSame('Sin datos', $this->stats(['anio' => 2025, 'periodo' => Periodo::Anual->value])[0]->getValue());
    }

    public function test_las_alertas_de_catastro_usan_el_anio_seleccionado(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $conPlan2025 = Equipo::factory()->create(['garantia_anio_vencimiento' => 2025, 'en_garantia' => true]);
        PlanMantenimiento::factory()->for($conPlan2025)->create(['anio' => 2025, 'frecuencia_anual' => FrecuenciaAnual::Dos]);

        $sinPlan2026 = $this->stat(CatastroAlertasWidget::class, ['anio' => 2026], 'Críticos sin plan 2026');
        $sinPlan2025 = $this->stat(CatastroAlertasWidget::class, ['anio' => 2025], 'Críticos sin plan 2025');

        // El equipo del setUp solo tiene plan 2026, y el nuevo solo 2025.
        $this->assertSame(1, $sinPlan2026->getValue());
        $this->assertSame(1, $sinPlan2025->getValue());
        $this->assertNotNull($sinPlan2026->getUrl());
        $this->assertNull($sinPlan2025->getUrl());
        $this->assertSame(1, $this->stat(CatastroAlertasWidget::class, ['anio' => 2025], 'Garantía vence en 2025')->getValue());
        $this->assertSame(0, $this->stat(CatastroAlertasWidget::class, ['anio' => 2026], 'Garantía vence este año')->getValue());
    }

    public function test_programa_sin_validar_segun_el_anio_seleccionado(): void
    {
        $this->actuarComo('Encargado de Mantención');
        Recinto::factory()->create();

        $this->assertNotNull($this->stat(CatastroAlertasWidget::class, ['anio' => 2026], 'Programa 2026 sin validar'));
        $this->assertNotNull($this->stat(CatastroAlertasWidget::class, ['anio' => 2025], 'Programa 2025 sin validar'));
        $this->assertNull($this->stat(CatastroAlertasWidget::class, ['anio' => 2027], 'Programa 2027 sin validar'));
    }

    public function test_convenios_sobregirados_del_anio_seleccionado(): void
    {
        $this->actuarComo('Encargado de Convenios');
        $convenio = Convenio::factory()->create(['monto_anual' => 100]);
        ConvenioEjecucionMensual::factory()->for($convenio)->create(['anio' => 2025, 'mes' => 1, 'monto' => 150]);

        $this->assertSame(1, $this->stat(AlertasConveniosWidget::class, ['anio' => 2025], 'Convenios sobregirados')->getValue());
        $this->assertSame(0, $this->stat(AlertasConveniosWidget::class, ['anio' => 2026], 'Convenios sobregirados')->getValue());
    }

    public function test_correctivos_del_anio_y_periodo_seleccionados(): void
    {
        $this->actuarComo('Encargado de Mantención');
        MantenimientoCorrectivo::factory()->create(['fecha' => '2025-02-10']);
        MantenimientoCorrectivo::factory()->create(['fecha' => '2025-08-10']);
        MantenimientoCorrectivo::factory()->create(['fecha' => '2026-03-10']);

        $anio2025 = $this->stat(IndicadoresGeneralesWidget::class, ['anio' => 2025], 'Correctivos 2025');
        $semestre = $this->stat(IndicadoresGeneralesWidget::class, ['anio' => 2025, 'periodo' => Periodo::PrimerSemestre->value], 'Correctivos del período');

        $this->assertSame(2, $anio2025->getValue());
        $this->assertSame([0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0], $anio2025->getChart());
        $this->assertSame(1, $semestre->getValue());
        $this->assertSame(1, $this->stat(IndicadoresGeneralesWidget::class, [], 'Correctivos este año')->getValue());
    }

    public function test_el_grafico_parte_en_el_anio_del_escritorio_y_respeta_el_cambio_local(): void
    {
        $this->actuarComo('Encargado de Mantención');

        Livewire::test(CumplimientoMpMensualWidget::class, ['anio' => 2025])
            ->assertSet('filter', '2025')
            ->set('filter', '2026')
            ->call('updateChartData')
            ->assertSet('filter', '2026');

        Livewire::test(CumplimientoMpMensualWidget::class)
            ->assertSet('filter', '2026');
    }

    /**
     * Los widgets reciben el período por el trait `RecibePeriodoDelEscritorio`: si Livewire no
     * reconociera el `#[Reactive]` declarado en el trait, el widget no seguiría los cambios del
     * Escritorio. Una propiedad reactiva no se puede modificar desde el propio widget.
     */
    public function test_el_periodo_recibido_por_el_trait_es_reactivo(): void
    {
        $this->actuarComo('Encargado de Mantención');

        $this->expectException(CannotMutateReactivePropException::class);

        Livewire::test(CatastroAlertasWidget::class, ['anio' => 2025])->set('anio', 2026);
    }
}
