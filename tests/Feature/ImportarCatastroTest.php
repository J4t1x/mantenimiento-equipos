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
        $this->assertSame('Raul Aravena Turra', Recinto::sole()->responsable_mp_nombre);
        $presupuesto = Recinto::sole()->presupuestosMantenimiento()->sole();
        $this->assertSame([2026, '450000000.00', '400000000.00'], [$presupuesto->anio, $presupuesto->gasto_programado_mp, $presupuesto->gasto_programado_mc]);
        $this->assertSame(469, Equipo::count());
        $this->assertSame(469, PlanMantenimiento::count());
        $this->assertGreaterThan(0, EjecucionMensual::where('estado', EstadoEjecucion::Programado)->count());

        $this->artisan('app:importar-catastro-coyhaique')->assertFailed();
        $this->assertSame(469, Equipo::count());
    }

    /**
     * RF-64: `--recinto` carga la misma estructura de planilla en otro establecimiento, y la
     * protección contra duplicados es por recinto, no global.
     */
    public function test_carga_la_planilla_en_otro_recinto_con_la_opcion_recinto(): void
    {
        $this->artisan('app:importar-catastro-coyhaique')->assertSuccessful();

        Recinto::create(['nombre' => 'Hospital de Puerto Aysén', 'responsable_mp_nombre' => 'Ana Pérez']);

        $this->artisan('app:importar-catastro-coyhaique', ['--recinto' => 'Hospital de Puerto Aysén'])->assertSuccessful();

        $puertoAysen = Recinto::where('nombre', 'Hospital de Puerto Aysén')->sole();
        $this->assertSame(2, Recinto::count());
        $this->assertSame(469, $puertoAysen->equipos()->count());
        $this->assertSame('Ana Pérez', $puertoAysen->responsable_mp_nombre, 'El importador no pisa una designación ya registrada (RF-70).');
        $this->assertSame(938, Equipo::count());

        $this->artisan('app:importar-catastro-coyhaique', ['--recinto' => 'Hospital de Puerto Aysén'])->assertFailed();
        $this->assertSame(938, Equipo::count());
    }
}
