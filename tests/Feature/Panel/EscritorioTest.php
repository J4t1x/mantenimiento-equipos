<?php

namespace Tests\Feature\Panel;

use App\Enums\Criticidad;
use App\Enums\EstadoEjecucion;
use App\Enums\EstadoEquipo;
use App\Enums\FrecuenciaAnual;
use App\Filament\Pages\Escritorio;
use App\Filament\Widgets\AlertasConveniosWidget;
use App\Filament\Widgets\CatastroAlertasWidget;
use App\Filament\Widgets\ConveniosEncargadoWidget;
use App\Filament\Widgets\CumplimientoMpMensualWidget;
use App\Filament\Widgets\CumplimientoMpWidget;
use App\Filament\Widgets\DistribucionCatastroCriticidadWidget;
use App\Filament\Widgets\DistribucionCatastroWidget;
use App\Filament\Widgets\GastoMensualWidget;
use App\Filament\Widgets\GastoMpWidget;
use App\Filament\Widgets\IndicadoresGeneralesWidget;
use App\Filament\Widgets\MisPlanesDelMesWidget;
use App\Models\Convenio;
use App\Models\ConvenioEjecucionMensual;
use App\Models\Equipo;
use App\Models\MantenimientoCorrectivo;
use App\Models\PlanMantenimiento;
use App\Models\User;
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
     * Widgets visibles por rol, en el orden de los grupos del Escritorio (SPEC-ME-007).
     *
     * @return array<string, array{string, array<int, class-string>, array<int, string>}>
     */
    public static function widgetsPorRol(): array
    {
        return [
            'Encargado de Mantención' => ['Encargado de Mantención', [
                CatastroAlertasWidget::class, AlertasConveniosWidget::class,
                CumplimientoMpWidget::class, GastoMpWidget::class, CumplimientoMpMensualWidget::class, GastoMensualWidget::class,
                IndicadoresGeneralesWidget::class, DistribucionCatastroWidget::class, DistribucionCatastroCriticidadWidget::class,
            ], ['Requiere atención', 'Mantenimiento preventivo', 'Catastro de equipos']],
            'Técnico Interno' => ['Técnico Interno', [
                MisPlanesDelMesWidget::class, CumplimientoMpWidget::class, CumplimientoMpMensualWidget::class,
            ], ['Mi trabajo del mes', 'Mantenimiento preventivo']],
            'Encargado de Convenios' => ['Encargado de Convenios', [
                AlertasConveniosWidget::class, GastoMpWidget::class, GastoMensualWidget::class, ConveniosEncargadoWidget::class,
            ], ['Requiere atención', 'Mantenimiento preventivo', 'Convenios']],
            'Jefatura' => ['Jefatura', [
                CatastroAlertasWidget::class,
                CumplimientoMpWidget::class, GastoMpWidget::class, CumplimientoMpMensualWidget::class, GastoMensualWidget::class,
                IndicadoresGeneralesWidget::class, DistribucionCatastroWidget::class, DistribucionCatastroCriticidadWidget::class,
            ], ['Requiere atención', 'Mantenimiento preventivo', 'Catastro de equipos']],
        ];
    }

    /**
     * @param  array<int, class-string>  $esperados
     * @param  array<int, string>  $grupos
     */
    #[DataProvider('widgetsPorRol')]
    public function test_cada_rol_ve_sus_grupos_y_widgets_en_orden(string $rol, array $esperados, array $grupos): void
    {
        $this->actuarComo($rol);

        $this->assertSame($esperados, Escritorio::widgetsVisibles());

        $respuesta = $this->get('/admin')->assertOk()->assertSeeInOrder($grupos);
        $ocultos = array_diff(array_column(Escritorio::GRUPOS, 'titulo'), $grupos);

        foreach ($ocultos as $grupo) {
            $respuesta->assertDontSee($grupo.'</', false);
        }

        $respuesta->assertDontSee('Bienvenida');
    }

    public function test_sin_casos_las_alertas_muestran_una_sola_tarjeta_sin_alertas_pendientes(): void
    {
        $this->actuarComo('Jefatura');

        $alertas = $this->invocar(new CatastroAlertasWidget, 'getStats');

        $this->assertCount(1, $alertas);
        $this->assertSame('Sin alertas pendientes', $alertas[0]->getLabel());
    }

    public function test_las_alertas_se_ordenan_por_gravedad(): void
    {
        $this->actuarComo('Encargado de Mantención');
        Equipo::factory()->create(['estado' => EstadoEquipo::SinEvaluar]);
        Equipo::factory()->create(['anio_adquisicion' => 2000, 'vida_util_anios' => 10]);

        $alertas = collect($this->invocar(new CatastroAlertasWidget, 'getStats'));
        $colores = $alertas->map(fn ($stat): string => $stat->getColor())->all();

        $this->assertContains('Equipos sin evaluar', $alertas->map(fn ($stat): string => $stat->getLabel()));
        $this->assertContains('warning', $colores);
        $this->assertContains('danger', $colores);
        // Todas las rojas antes que las amarillas, y ninguna en 0.
        $this->assertSame($colores, collect($colores)->sortBy(fn (string $color): int => $color === 'danger' ? 0 : 1)->values()->all());
        $this->assertTrue($alertas->every(fn ($stat): bool => $stat->getValue() > 0));
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

    public function test_equipos_criticos_cuenta_solo_equipos_activos(): void
    {
        $this->actuarComo('Jefatura');
        Equipo::factory()->count(2)->create(['criticidad' => Criticidad::Critico]);
        Equipo::factory()->create(['criticidad' => Criticidad::Critico, 'activo' => false]);

        [, $criticos] = $this->invocar(new IndicadoresGeneralesWidget, 'getStats');

        $this->assertSame(2, $criticos->getValue());
    }

    public function test_las_tarjetas_de_alerta_enlazan_al_listado_filtrado(): void
    {
        $tecnico = User::factory()->create();
        $this->actuarComo('Encargado de Mantención');
        // Las alertas solo se muestran con casos (SPEC-ME-007).
        Equipo::factory()->create(['estado' => EstadoEquipo::SinEvaluar]);
        Convenio::factory()->create(['fecha_expiracion' => now()->addDays(10)]);

        $sinEvaluar = collect($this->invocar(new CatastroAlertasWidget, 'getStats'))->first(fn ($stat): bool => $stat->getLabel() === 'Equipos sin evaluar');
        $porVencer = collect($this->invocar(new AlertasConveniosWidget, 'getStats'))->first(fn ($stat): bool => $stat->getLabel() === 'Convenios por vencer');

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
