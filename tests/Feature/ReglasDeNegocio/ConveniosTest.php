<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Actions\CalcularEjecucionConvenioAction;
use App\Models\Convenio;
use App\Models\ConvenioEjecucionMensual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RN-05 (RF-29/RF-30): la ejecución mensual de un convenio se concilia contra su monto anual; si lo
 * supera, el sistema alerta sin bloquear el registro, y la alerta queda en la campana del usuario.
 */
class ConveniosTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_el_porcentaje_de_ejecucion_del_anio(): void
    {
        $convenio = Convenio::factory()->create(['monto_anual' => 1_000_000]);
        ConvenioEjecucionMensual::factory()->for($convenio)->create(['anio' => 2026, 'mes' => 1, 'monto' => 300_000]);
        ConvenioEjecucionMensual::factory()->for($convenio)->create(['anio' => 2025, 'mes' => 1, 'monto' => 900_000]);

        $ejecucion = app(CalcularEjecucionConvenioAction::class)->paraConvenio($convenio, 2026);

        $this->assertSame(30.0, $ejecucion['porcentaje']);
        $this->assertFalse($ejecucion['sobregirado']);
    }

    public function test_el_sobregiro_alerta_sin_bloquear_y_queda_en_la_campana(): void
    {
        $usuario = $this->actuarComo('Encargado de Convenios');
        $convenio = Convenio::factory()->create(['monto_anual' => 100_000]);

        ConvenioEjecucionMensual::factory()->for($convenio)->create(['anio' => 2026, 'mes' => 1, 'monto' => 80_000]);
        $this->assertSame(0, $usuario->notifications()->count());

        ConvenioEjecucionMensual::factory()->for($convenio)->create(['anio' => 2026, 'mes' => 2, 'monto' => 40_000]);

        $this->assertSame(2, $convenio->ejecucionesMensuales()->count());
        $this->assertTrue(app(CalcularEjecucionConvenioAction::class)->paraConvenio($convenio, 2026)['sobregirado']);
        $this->assertSame(1, $usuario->notifications()->count());
        $this->assertSame('Convenio sobregirado', $usuario->notifications()->first()->data['title']);
    }

    public function test_cuenta_convenios_sobregirados_y_por_vencer(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 15));

        $sobregirado = Convenio::factory()->create(['monto_anual' => 100, 'fecha_expiracion' => '2026-10-01']);
        ConvenioEjecucionMensual::factory()->for($sobregirado)->create(['anio' => 2026, 'monto' => 101]);
        Convenio::factory()->create(['fecha_expiracion' => '2027-06-30']);
        Convenio::factory()->create(['fecha_expiracion' => '2026-01-31']);

        $accion = app(CalcularEjecucionConvenioAction::class);

        $this->assertSame(1, $accion->contarSobregirados(2026));
        $this->assertSame(1, $accion->contarPorVencer(60));
    }
}
