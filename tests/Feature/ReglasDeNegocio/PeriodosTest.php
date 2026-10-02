<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Actions\CalcularCumplimientoMpAction;
use App\Actions\ObtenerDetalleGastoAction;
use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Enums\Periodo;
use App\Models\Convenio;
use App\Models\ConvenioEjecucionMensual;
use App\Models\Equipo;
use App\Models\MantenimientoCorrectivo;
use App\Models\PlanMantenimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RF-67: el cumplimiento de MP y el detalle de gasto se pueden acotar a un trimestre o semestre.
 */
class PeriodosTest extends TestCase
{
    use RefreshDatabase;

    private const ANIO = 2026;

    public function test_cada_periodo_cubre_sus_meses(): void
    {
        $this->assertSame(range(1, 12), Periodo::Anual->meses());
        $this->assertSame(range(1, 6), Periodo::PrimerSemestre->meses());
        $this->assertSame([10, 11, 12], Periodo::CuartoTrimestre->meses());
        $this->assertSame('2026-04-01', Periodo::SegundoTrimestre->inicio(self::ANIO)->toDateString());
        $this->assertSame('2026-06-30', Periodo::SegundoTrimestre->fin(self::ANIO)->toDateString());
    }

    public function test_cumplimiento_por_semestre_y_trimestre(): void
    {
        $plan = PlanMantenimiento::factory()->create(['anio' => self::ANIO, 'frecuencia_anual' => FrecuenciaAnual::Cuatro]);

        foreach ([3, 9] as $mes) {
            $plan->ejecucionesMensuales()->where('mes', $mes)->first()->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => now()]);
        }

        $accion = app(CalcularCumplimientoMpAction::class);
        $total = fn (?Periodo $periodo): array => $accion->execute(self::ANIO, periodo: $periodo)['total'];

        $this->assertSame(['programados' => 4, 'ejecutados' => 2, 'porcentaje' => 50.0], $total(null));
        $this->assertSame(['programados' => 4, 'ejecutados' => 2, 'porcentaje' => 50.0], $total(Periodo::Anual));
        $this->assertSame(['programados' => 2, 'ejecutados' => 1, 'porcentaje' => 50.0], $total(Periodo::PrimerSemestre));
        $this->assertSame(['programados' => 1, 'ejecutados' => 1, 'porcentaje' => 100.0], $total(Periodo::TercerTrimestre));
        $this->assertSame(['programados' => 1, 'ejecutados' => 0, 'porcentaje' => 0.0], $total(Periodo::CuartoTrimestre));
    }

    public function test_detalle_de_gasto_por_trimestre_prorratea_lo_programado(): void
    {
        $convenio = Convenio::factory()->create();
        $equipo = Equipo::factory()->create();
        PlanMantenimiento::factory()->for($equipo)->create([
            'anio' => self::ANIO,
            'frecuencia_anual' => FrecuenciaAnual::Cuatro,
            'convenio_id' => $convenio->id,
            'costo_anual_referencia' => 1_200_000,
        ]);
        ConvenioEjecucionMensual::factory()->for($convenio)->create(['anio' => self::ANIO, 'mes' => 2, 'monto' => 150_000]);
        ConvenioEjecucionMensual::factory()->for($convenio)->create(['anio' => self::ANIO, 'mes' => 8, 'monto' => 90_000]);
        MantenimientoCorrectivo::factory()->for($equipo)->create(['fecha' => '2026-03-31', 'costo' => 40_000]);
        MantenimientoCorrectivo::factory()->for($equipo)->create(['fecha' => '2026-04-01', 'costo' => 25_000]);

        $accion = app(ObtenerDetalleGastoAction::class);
        $primerTrimestre = $accion->execute(self::ANIO, periodo: Periodo::PrimerTrimestre);
        $segundoSemestre = $accion->execute(self::ANIO, periodo: Periodo::SegundoSemestre);

        $this->assertSame(['programado' => 300_000.0, 'ejecutado' => 150_000.0, 'porcentaje' => 50.0, 'fuente' => 'planes'], $primerTrimestre['mp']);
        $this->assertSame(40_000.0, $primerTrimestre['mc']['ejecutado']);
        $this->assertSame(['programado' => 600_000.0, 'ejecutado' => 90_000.0, 'porcentaje' => 15.0, 'fuente' => 'planes'], $segundoSemestre['mp']);
        $this->assertSame(0.0, $segundoSemestre['mc']['ejecutado']);
    }
}
