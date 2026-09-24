<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Actions\CalcularCumplimientoMpAction;
use App\Actions\CalcularGastoCorrectivoAction;
use App\Actions\ObtenerDetalleGastoAction;
use App\Enums\Criticidad;
use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Models\Convenio;
use App\Models\ConvenioEjecucionMensual;
use App\Models\Equipo;
use App\Models\MantenimientoCorrectivo;
use App\Models\PlanMantenimiento;
use App\Models\Recinto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RN-03 (cumplimiento MP = ejecutados / programados, en 4 cortes) y RN-04 (gasto MC suma al
 * período), más el detalle de gasto MP vs. MC (RF-33) y su filtro por recinto (RF-54).
 */
class IndicadoresTest extends TestCase
{
    use RefreshDatabase;

    private const ANIO = 2026;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(self::ANIO, 9, 15));
    }

    /**
     * Plan trimestral (mar/jun/sep/dic) con los meses indicados ya realizados.
     *
     * @param  array<int, int>  $mesesRealizados
     */
    private function planTrimestral(Equipo $equipo, array $mesesRealizados, array $atributos = []): PlanMantenimiento
    {
        $plan = PlanMantenimiento::factory()->for($equipo)->create([
            'anio' => self::ANIO,
            'frecuencia_anual' => FrecuenciaAnual::Cuatro,
            ...$atributos,
        ]);

        foreach ($mesesRealizados as $mes) {
            $plan->ejecucionesMensuales()->where('mes', $mes)->first()
                ->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => now()]);
        }

        return $plan;
    }

    public function test_cumplimiento_por_cortes_de_criticidad(): void
    {
        $this->planTrimestral(Equipo::factory()->create(['criticidad' => Criticidad::Critico]), [3, 6]);
        $this->planTrimestral(Equipo::factory()->create(['criticidad' => Criticidad::Relevante]), [3]);
        $this->planTrimestral(Equipo::factory()->create(['criticidad' => Criticidad::NoAplica]), []);

        $cortes = app(CalcularCumplimientoMpAction::class)->execute(self::ANIO);

        $this->assertSame(['programados' => 12, 'ejecutados' => 3, 'porcentaje' => 25.0], $cortes['total']);
        $this->assertSame(['programados' => 4, 'ejecutados' => 2, 'porcentaje' => 50.0], $cortes[Criticidad::Critico->value]);
        $this->assertSame(['programados' => 4, 'ejecutados' => 1, 'porcentaje' => 25.0], $cortes[Criticidad::Relevante->value]);
        $this->assertSame(['programados' => 0, 'ejecutados' => 0, 'porcentaje' => null], $cortes[Criticidad::ImMayorIgual12->value]);
    }

    public function test_un_mes_reprogramado_cuenta_como_programado_pero_no_como_ejecutado(): void
    {
        $plan = $this->planTrimestral(Equipo::factory()->create(), [3]);
        $plan->ejecucionesMensuales()->where('mes', 6)->first()->update(['estado' => EstadoEjecucion::Reprogramado]);

        $total = app(CalcularCumplimientoMpAction::class)->execute(self::ANIO)['total'];

        $this->assertSame(4, $total['programados']);
        $this->assertSame(1, $total['ejecutados']);
    }

    public function test_cumplimiento_mes_a_mes_cuadra_con_el_total_anual(): void
    {
        $this->planTrimestral(Equipo::factory()->create(), [3, 9]);

        $accion = app(CalcularCumplimientoMpAction::class);
        $meses = $accion->porMes(self::ANIO);
        $total = $accion->execute(self::ANIO)['total'];

        $this->assertCount(12, $meses);
        $this->assertSame(['programados' => 1, 'ejecutados' => 1, 'porcentaje' => 100.0], $meses[3]);
        $this->assertSame(['programados' => 1, 'ejecutados' => 0, 'porcentaje' => 0.0], $meses[6]);
        $this->assertNull($meses[1]['porcentaje']);
        $this->assertSame($total['programados'], array_sum(array_column($meses, 'programados')));
        $this->assertSame($total['ejecutados'], array_sum(array_column($meses, 'ejecutados')));
    }

    public function test_cumplimiento_filtrado_por_recinto(): void
    {
        $recinto = Recinto::factory()->create();
        $this->planTrimestral(Equipo::factory()->for($recinto)->create(), [3, 6, 9]);
        $this->planTrimestral(Equipo::factory()->create(), []);

        $total = app(CalcularCumplimientoMpAction::class)->execute(self::ANIO, recintoId: $recinto->id)['total'];

        $this->assertSame(['programados' => 4, 'ejecutados' => 3, 'porcentaje' => 75.0], $total);
    }

    public function test_gasto_correctivo_suma_al_periodo_y_excluye_eliminados(): void
    {
        $equipo = Equipo::factory()->create();
        MantenimientoCorrectivo::factory()->for($equipo)->create(['fecha' => '2026-03-10', 'costo' => 200_000]);
        MantenimientoCorrectivo::factory()->for($equipo)->create(['fecha' => '2026-03-20', 'costo' => 50_000]);
        MantenimientoCorrectivo::factory()->for($equipo)->create(['fecha' => '2025-12-31', 'costo' => 999_999]);
        MantenimientoCorrectivo::factory()->for($equipo)->create(['fecha' => '2026-05-01', 'costo' => 70_000])->delete();

        $accion = app(CalcularGastoCorrectivoAction::class);

        $this->assertSame(250_000.0, $accion->execute(self::ANIO));
        $this->assertSame(250_000.0, $accion->porMes(self::ANIO)[3]);
        $this->assertSame(0.0, $accion->porMes(self::ANIO)[5]);
        $this->assertSame(250_000.0, $accion->paraEquipo($equipo->id, self::ANIO));
    }

    public function test_detalle_de_gasto_preventivo_vs_correctivo(): void
    {
        $convenio = Convenio::factory()->create();
        $this->planTrimestral(Equipo::factory()->create(), [], ['convenio_id' => $convenio->id, 'costo_anual_referencia' => 400_000]);
        ConvenioEjecucionMensual::factory()->for($convenio)->create(['anio' => self::ANIO, 'mes' => 3, 'monto' => 100_000]);
        MantenimientoCorrectivo::factory()->create(['fecha' => '2026-04-01', 'costo' => 30_000]);

        $detalle = app(ObtenerDetalleGastoAction::class)->execute(self::ANIO);

        $this->assertSame(['programado' => 400_000.0, 'ejecutado' => 100_000.0, 'porcentaje' => 25.0], $detalle['mp']);
        $this->assertSame(['programado' => null, 'ejecutado' => 30_000.0, 'porcentaje' => null], $detalle['mc']);
    }
}
