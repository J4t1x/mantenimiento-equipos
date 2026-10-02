<?php

namespace Tests\Feature\Panel;

use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Enums\Periodo;
use App\Filament\Pages\Escritorio;
use App\Filament\Widgets\CumplimientoMpWidget;
use App\Models\PlanMantenimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * RF-21 (cierre): el Escritorio tiene su propio selector de año y período (año, semestre, trimestre
 * o mes) para el indicador de cumplimiento, sin el error 500 de `HasFiltersForm`.
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
    private function stats(array $propiedades): array
    {
        $widget = Livewire::test(CumplimientoMpWidget::class, $propiedades)->instance();

        return (new ReflectionMethod($widget, 'getStats'))->invoke($widget);
    }

    public function test_el_escritorio_con_selector_de_periodo_se_renderiza_sin_error(): void
    {
        $this->actuarComo('Encargado de Mantención');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Período del indicador de cumplimiento');
    }

    public function test_sin_permiso_de_cumplimiento_no_se_muestra_el_selector(): void
    {
        $this->actuarComo('Encargado de Convenios');

        $this->get('/admin')
            ->assertOk()
            ->assertDontSee('Período del indicador de cumplimiento');
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
}
