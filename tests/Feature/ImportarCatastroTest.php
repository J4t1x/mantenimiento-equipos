<?php

namespace Tests\Feature;

use App\Enums\EstadoEjecucion;
use App\Models\EjecucionMensual;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use App\Models\Recinto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Carga del catastro real del Hospital Regional Coyhaique desde la planilla vigente.
 */
class ImportarCatastroTest extends TestCase
{
    use RefreshDatabase;

    public function test_importa_los_469_equipos_con_su_plan_y_no_duplica_sin_force(): void
    {
        $this->artisan('app:importar-catastro-coyhaique')->assertSuccessful();

        $this->assertSame(1, Recinto::count());
        $this->assertSame(469, Equipo::count());
        $this->assertSame(469, PlanMantenimiento::count());
        $this->assertGreaterThan(0, EjecucionMensual::where('estado', EstadoEjecucion::Programado)->count());

        $this->artisan('app:importar-catastro-coyhaique')->assertFailed();
        $this->assertSame(469, Equipo::count());
    }
}
