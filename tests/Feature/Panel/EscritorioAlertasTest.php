<?php

namespace Tests\Feature\Panel;

use App\Actions\ObtenerDetalleGastoAction;
use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Enums\Periodo;
use App\Enums\TipoMantenimiento;
use App\Filament\Resources\EjecucionesMensuales\Pages\ListEjecucionesMensuales;
use App\Filament\Widgets\CatastroAlertasWidget;
use App\Filament\Widgets\GastoMpWidget;
use App\Filament\Widgets\MisPlanesDelMesWidget;
use App\Models\Convenio;
use App\Models\ConvenioEjecucionMensual;
use App\Models\EjecucionMensual;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use App\Models\PresupuestoMantenimiento;
use App\Models\Recinto;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * RF-80 y RF-81 (Módulo 19): alerta de MP atrasada y % de gasto MP ejecutado en el Escritorio.
 */
class EscritorioAlertasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 10, 15));
    }

    /**
     * Plan con los meses indicados en "Programado" (o en el estado dado) y el resto sin programar.
     * Se actualiza sin eventos de modelo para fijar el escenario sin pasar por RN-01/RN-02.
     *
     * @param  array<int, EstadoEjecucion>  $estadosPorMes
     * @param  array<string, mixed>  $equipo
     */
    private function planCon(array $estadosPorMes, int $anio = 2026, array $equipo = [], ?User $responsable = null): PlanMantenimiento
    {
        $plan = PlanMantenimiento::factory()
            ->for(Equipo::factory()->create($equipo))
            ->create(['anio' => $anio, 'frecuencia_anual' => FrecuenciaAnual::Dos, 'responsable_interno_id' => $responsable?->id]);

        EjecucionMensual::query()->where('plan_mantenimiento_id', $plan->id)->update(['estado' => EstadoEjecucion::SinProgramar]);

        foreach ($estadosPorMes as $mes => $estado) {
            EjecucionMensual::query()->where('plan_mantenimiento_id', $plan->id)->where('mes', $mes)->update(['estado' => $estado]);
        }

        return $plan;
    }

    private function stat(string $widget, array $propiedades, string $etiqueta): ?Stat
    {
        $instancia = Livewire::test($widget, $propiedades)->instance();
        $stats = (new ReflectionMethod($instancia, 'getStats'))->invoke($instancia);

        return collect($stats)->first(fn (Stat $stat): bool => $stat->getLabel() === $etiqueta);
    }

    public function test_un_mes_programado_anterior_al_actual_esta_atrasado(): void
    {
        $atrasado = $this->planCon([3 => EstadoEjecucion::Programado]);
        $this->planCon([10 => EstadoEjecucion::Programado]);
        $this->planCon([3 => EstadoEjecucion::Reprogramado]);
        $this->planCon([3 => EstadoEjecucion::Realizado]);
        $this->planCon([3 => EstadoEjecucion::Programado], equipo: ['activo' => false]);

        $planes = EjecucionMensual::query()->atrasadas(2026)->pluck('plan_mantenimiento_id')->unique()->values()->all();

        $this->assertSame([$atrasado->id], $planes);
    }

    public function test_en_un_anio_pasado_cuentan_los_12_meses_y_en_uno_futuro_ninguno(): void
    {
        $this->planCon([12 => EstadoEjecucion::Programado], anio: 2025);
        $this->planCon([1 => EstadoEjecucion::Programado], anio: 2027);

        $this->assertSame(1, EjecucionMensual::query()->atrasadas(2025)->count());
        $this->assertSame(0, EjecucionMensual::query()->atrasadas(2027)->count());
    }

    public function test_la_tarjeta_cuenta_equipos_y_se_marca_en_rojo_con_criticos(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $this->planCon([2 => EstadoEjecucion::Programado, 3 => EstadoEjecucion::Programado, 4 => EstadoEjecucion::Programado]);
        $this->planCon([5 => EstadoEjecucion::Programado], equipo: ['criticidad' => 'relevante']);

        $tarjeta = $this->stat(CatastroAlertasWidget::class, ['anio' => 2026], 'MP atrasada');

        $this->assertSame(2, $tarjeta->getValue());
        $this->assertSame('danger', $tarjeta->getColor());
        $this->assertStringContainsString('(1 críticos)', $tarjeta->getDescription());
        $this->assertStringContainsString('filters[atrasadas][isActive]=1', urldecode($tarjeta->getUrl()));
    }

    public function test_solo_relevantes_atrasados_se_marca_como_advertencia(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $this->planCon([5 => EstadoEjecucion::Programado], equipo: ['criticidad' => 'relevante']);

        $tarjeta = $this->stat(CatastroAlertasWidget::class, ['anio' => 2026], 'MP atrasada');

        $this->assertSame('warning', $tarjeta->getColor());
    }

    public function test_en_un_anio_pasado_enlaza_con_el_anio_y_el_estado_programado(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $this->planCon([12 => EstadoEjecucion::Programado], anio: 2025);

        $tarjeta = $this->stat(CatastroAlertasWidget::class, ['anio' => 2025], 'MP atrasada');
        $url = urldecode($tarjeta->getUrl());

        $this->assertSame(1, $tarjeta->getValue());
        $this->assertStringContainsString('filters[anio][value]=2025', $url);
        $this->assertStringContainsString('filters[estado][value]=programado', $url);
        $this->assertNull($this->stat(CatastroAlertasWidget::class, ['anio' => 2027], 'MP atrasada')->getUrl());
    }

    public function test_respeta_el_alcance_por_recinto(): void
    {
        [$propio, $ajeno] = Recinto::factory()->count(2)->create();
        $this->planCon([3 => EstadoEjecucion::Programado], equipo: ['recinto_id' => $propio->id]);
        $this->planCon([3 => EstadoEjecucion::Programado], equipo: ['recinto_id' => $ajeno->id]);
        $usuario = User::factory()->create();
        $usuario->recintos()->attach($propio->id);
        $this->actuarComo('Encargado de Mantención', $usuario);

        $this->assertSame(1, $this->stat(CatastroAlertasWidget::class, ['anio' => 2026], 'MP atrasada')->getValue());
    }

    public function test_el_filtro_de_la_bitacora_muestra_solo_los_meses_atrasados(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $atrasado = $this->planCon([3 => EstadoEjecucion::Programado]);
        $alDia = $this->planCon([10 => EstadoEjecucion::Programado]);

        Livewire::test(ListEjecucionesMensuales::class)
            ->filterTable('atrasadas')
            ->assertCanSeeTableRecords($atrasado->ejecucionesMensuales()->where('mes', 3)->get())
            ->assertCanNotSeeTableRecords($alDia->ejecucionesMensuales()->get());
    }

    public function test_el_tecnico_ve_solo_sus_equipos_atrasados(): void
    {
        $tecnico = User::factory()->create();
        $this->planCon([3 => EstadoEjecucion::Programado], responsable: $tecnico);
        $this->planCon([4 => EstadoEjecucion::Programado]);
        $this->actuarComo('Técnico Interno', $tecnico);

        $this->assertSame(1, $this->stat(MisPlanesDelMesWidget::class, [], 'MP atrasada')->getValue());
    }

    public function test_el_gasto_mp_coincide_con_el_detalle_de_gasto_de_reportes(): void
    {
        $this->actuarComo('Encargado de Convenios');
        $recinto = Recinto::factory()->create();
        PresupuestoMantenimiento::factory()->for($recinto)->create(['anio' => 2026, 'gasto_programado_mp' => 1_000_000, 'gasto_programado_mc' => 500_000]);
        $convenio = Convenio::factory()->create();
        PlanMantenimiento::factory()
            ->for(Equipo::factory()->create(['recinto_id' => $recinto->id]))
            ->create(['anio' => 2026, 'tipo_mantenimiento' => TipoMantenimiento::Externo, 'convenio_id' => $convenio->id, 'proveedor_id' => $convenio->proveedor_id]);
        ConvenioEjecucionMensual::factory()->for($convenio)->create(['anio' => 2026, 'mes' => 2, 'monto' => 250_000]);

        $anual = $this->stat(GastoMpWidget::class, ['anio' => 2026], 'Gasto MP ejecutado');
        $semestre = $this->stat(GastoMpWidget::class, ['anio' => 2026, 'periodo' => Periodo::PrimerSemestre->value], 'Gasto MP ejecutado');
        $detalle = app(ObtenerDetalleGastoAction::class)->execute(2026);

        $this->assertSame('25%', $anual->getValue());
        $this->assertSame("{$detalle['mp']['porcentaje']}%", $anual->getValue());
        $this->assertSame('$250.000 de $1.000.000 programado', $anual->getDescription());
        $this->assertSame('50%', $semestre->getValue());
    }

    public function test_sin_gasto_programado_lo_indica(): void
    {
        $this->actuarComo('Jefatura');

        $this->assertSame('Sin gasto programado', $this->stat(GastoMpWidget::class, ['anio' => 2026], 'Gasto MP ejecutado')->getValue());
    }
}
