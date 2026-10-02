<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Exceptions\EquipoNoAsignadoException;
use App\Exceptions\TransicionBitacoraInvalidaException;
use App\Models\EjecucionMensual;
use App\Models\PlanMantenimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RN-02: un mes solo puede quedar "Realizado" o "Reprogramado" si antes estaba programado.
 * RF-20: un Técnico Interno solo registra la ejecución de los planes que tiene asignados.
 * Ambas reglas se validan en el modelo, no solo en el formulario (RNF-04).
 */
class BitacoraTest extends TestCase
{
    use RefreshDatabase;

    private function mes(PlanMantenimiento $plan, int $mes): EjecucionMensual
    {
        return $plan->ejecucionesMensuales()->where('mes', $mes)->firstOrFail();
    }

    public function test_un_mes_programado_puede_marcarse_realizado_o_reprogramado(): void
    {
        $plan = PlanMantenimiento::factory()->create(['frecuencia_anual' => FrecuenciaAnual::Cuatro]);

        $this->mes($plan, 3)->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => now()]);
        $this->mes($plan, 6)->update(['estado' => EstadoEjecucion::Reprogramado, 'observaciones' => 'Equipo en uso en pabellón']);

        $this->assertSame(EstadoEjecucion::Realizado, $this->mes($plan, 3)->estado);
        $this->assertSame(EstadoEjecucion::Reprogramado, $this->mes($plan, 6)->estado);
    }

    public function test_un_mes_sin_programar_no_puede_marcarse_realizado(): void
    {
        $plan = PlanMantenimiento::factory()->create(['frecuencia_anual' => FrecuenciaAnual::Cuatro]);

        $this->expectException(TransicionBitacoraInvalidaException::class);

        $this->mes($plan, 1)->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => now()]);
    }

    public function test_un_tecnico_interno_solo_registra_sus_planes_asignados(): void
    {
        $tecnico = User::factory()->create();
        $propio = PlanMantenimiento::factory()->create(['responsable_interno_id' => $tecnico->id]);
        $ajeno = PlanMantenimiento::factory()->create();
        $this->actuarComo('Técnico Interno', $tecnico);

        $this->mes($propio, 3)->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => now()]);
        $this->assertSame(EstadoEjecucion::Realizado, $this->mes($propio, 3)->estado);

        $this->expectException(EquipoNoAsignadoException::class);
        $this->mes($ajeno, 3)->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => now()]);
    }

    public function test_el_encargado_de_mantencion_registra_cualquier_plan(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $plan = PlanMantenimiento::factory()->create();

        $this->mes($plan, 3)->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => now()]);

        $this->assertSame(EstadoEjecucion::Realizado, $this->mes($plan, 3)->estado);
    }
}
