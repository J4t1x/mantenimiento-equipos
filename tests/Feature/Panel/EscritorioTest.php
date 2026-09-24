<?php

namespace Tests\Feature\Panel;

use App\Enums\Criticidad;
use App\Enums\EstadoEjecucion;
use App\Enums\EstadoEquipo;
use App\Enums\FrecuenciaAnual;
use App\Filament\Widgets\AlertasConveniosWidget;
use App\Filament\Widgets\CatastroAlertasWidget;
use App\Filament\Widgets\ConveniosEncargadoWidget;
use App\Filament\Widgets\CumplimientoMpMensualWidget;
use App\Filament\Widgets\CumplimientoMpWidget;
use App\Filament\Widgets\DistribucionCatastroCriticidadWidget;
use App\Filament\Widgets\DistribucionCatastroWidget;
use App\Filament\Widgets\GastoMensualWidget;
use App\Filament\Widgets\IndicadoresGeneralesWidget;
use App\Filament\Widgets\MisPlanesDelMesWidget;
use App\Models\ConvenioEjecucionMensual;
use App\Models\Equipo;
use App\Models\MantenimientoCorrectivo;
use App\Models\PlanMantenimiento;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\AccountWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Escritorio (RF-43 a RF-46 y RF-57 a RF-61): widgets visibles por rol, orden, gráficos y enlaces.
 */
class EscritorioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 9, 15));
    }

    private function invocar(object $widget, string $metodo): array
    {
        return (new ReflectionMethod($widget, $metodo))->invoke($widget);
    }

    /**
     * @return array<string, array{string, array<int, class-string>}>
     */
    public static function widgetsPorRol(): array
    {
        return [
            'Encargado de Mantención' => ['Encargado de Mantención', [
                AlertasConveniosWidget::class, CatastroAlertasWidget::class, CumplimientoMpWidget::class,
                CumplimientoMpMensualWidget::class, GastoMensualWidget::class, DistribucionCatastroWidget::class,
                DistribucionCatastroCriticidadWidget::class, IndicadoresGeneralesWidget::class,
            ]],
            'Técnico Interno' => ['Técnico Interno', [
                MisPlanesDelMesWidget::class, CumplimientoMpWidget::class, CumplimientoMpMensualWidget::class,
            ]],
            'Encargado de Convenios' => ['Encargado de Convenios', [
                AlertasConveniosWidget::class, GastoMensualWidget::class, ConveniosEncargadoWidget::class,
            ]],
            'Jefatura' => ['Jefatura', [
                CatastroAlertasWidget::class, CumplimientoMpWidget::class, CumplimientoMpMensualWidget::class,
                GastoMensualWidget::class, DistribucionCatastroWidget::class, DistribucionCatastroCriticidadWidget::class,
                IndicadoresGeneralesWidget::class,
            ]],
        ];
    }

    /**
     * @param  array<int, class-string>  $esperados
     */
    #[DataProvider('widgetsPorRol')]
    public function test_cada_rol_ve_sus_widgets_en_orden_de_jerarquia(string $rol, array $esperados): void
    {
        $this->actuarComo($rol);

        $visibles = collect(Filament::getPanel('admin')->getWidgets())
            ->map(fn ($widget): string => is_string($widget) ? $widget : $widget->widget)
            ->reject(fn (string $widget): bool => $widget === AccountWidget::class)
            ->filter(fn (string $widget): bool => $widget::canView())
            ->values()
            ->all();

        $this->assertSame($esperados, $visibles);
        $this->get('/admin')->assertOk();
    }

    public function test_grafico_de_cumplimiento_mensual_con_filtro_de_anio(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $plan = PlanMantenimiento::factory()->create(['anio' => 2026, 'frecuencia_anual' => FrecuenciaAnual::Cuatro]);
        $plan->ejecucionesMensuales()->where('mes', 3)->first()->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => now()]);

        $componente = Livewire::test(CumplimientoMpMensualWidget::class)->assertOk()->assertSet('filter', '2026');
        [$linea, $programados, $ejecutados] = $this->invocar($componente->instance(), 'getData')['datasets'];

        $this->assertSame([0, 0, 1, 0, 0, 1, 0, 0, 1, 0, 0, 1], $programados['data']);
        $this->assertSame([0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0], $ejecutados['data']);
        $this->assertSame([null, null, 100.0, 100.0, 100.0, 50.0, 50.0, 50.0, 33.3, 33.3, 33.3, 25.0], $linea['data']);

        $componente->set('filter', '2025')->assertOk();
        $this->assertSame(array_fill(0, 12, 0), $this->invocar($componente->instance(), 'getData')['datasets'][1]['data']);
    }

    public function test_grafico_de_gasto_mensual_apila_preventivo_y_correctivo(): void
    {
        $this->actuarComo('Encargado de Convenios');
        ConvenioEjecucionMensual::factory()->create(['anio' => 2026, 'mes' => 3, 'monto' => 1_000]);
        MantenimientoCorrectivo::factory()->create(['fecha' => '2026-03-05', 'costo' => 500]);

        [$mp, $mc] = $this->invocar(Livewire::test(GastoMensualWidget::class)->assertOk()->instance(), 'getData')['datasets'];

        $this->assertSame(1_000.0, $mp['data'][2]);
        $this->assertSame(500.0, $mc['data'][2]);
        $this->assertSame(1_500.0, array_sum($mp['data']) + array_sum($mc['data']));
    }

    public function test_donas_de_catastro_cuentan_solo_equipos_activos(): void
    {
        $this->actuarComo('Jefatura');
        Equipo::factory()->count(2)->create(['estado' => EstadoEquipo::Bueno, 'criticidad' => Criticidad::Critico]);
        Equipo::factory()->create(['estado' => EstadoEquipo::SinEvaluar, 'criticidad' => Criticidad::NoAplica]);
        Equipo::factory()->create(['estado' => EstadoEquipo::Malo, 'activo' => false]);

        $estado = $this->invocar(Livewire::test(DistribucionCatastroWidget::class)->instance(), 'getData');
        $criticidad = $this->invocar(Livewire::test(DistribucionCatastroCriticidadWidget::class)->instance(), 'getData');

        $this->assertSame([2, 0, 0, 1], $estado['datasets'][0]['data']);
        $this->assertSame([2, 0, 0, 1], $criticidad['datasets'][0]['data']);
    }

    public function test_las_tarjetas_de_alerta_enlazan_al_listado_filtrado(): void
    {
        $tecnico = User::factory()->create();
        $this->actuarComo('Encargado de Mantención');

        [$sinEvaluar] = $this->invocar(new CatastroAlertasWidget, 'getStats');
        [, $porVencer] = $this->invocar(new AlertasConveniosWidget, 'getStats');

        $this->assertStringContainsString('filters[estado][value]=sin_evaluar', urldecode($sinEvaluar->getUrl()));
        $this->assertStringContainsString('filters[vigencia][value]=vigente', urldecode($porVencer->getUrl()));

        $this->actuarComo('Técnico Interno', $tecnico);
        [$pendientes] = $this->invocar(new MisPlanesDelMesWidget, 'getStats');

        $this->assertStringContainsString('filters[anio][value]=2026', urldecode($pendientes->getUrl()));
        $this->assertStringContainsString('filters[estado][value]=programado', urldecode($pendientes->getUrl()));
    }

    public function test_tendencia_de_12_meses_en_correctivos(): void
    {
        $this->actuarComo('Encargado de Mantención');
        MantenimientoCorrectivo::factory()->create(['fecha' => '2025-10-10']);
        MantenimientoCorrectivo::factory()->create(['fecha' => '2026-09-01']);
        MantenimientoCorrectivo::factory()->create(['fecha' => '2025-09-10']);

        $correctivos = collect($this->invocar(new IndicadoresGeneralesWidget, 'getStats'))->last()->getChart();

        $this->assertSame([1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1], $correctivos);
    }
}
