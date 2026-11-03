<?php

namespace Tests\Feature\Panel;

use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Filament\Pages\BitacoraAnual;
use App\Filament\Resources\PlanMantenimientos\Pages\ViewPlanMantenimiento;
use App\Filament\Resources\PlanMantenimientos\RelationManagers\EjecucionesMensualesRelationManager;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SPEC-ME-013 (hallazgos H-01, M-02 y B-01 de la prueba funcional del 03-11-2026): solo quien tiene
 * permiso de edición de la bitácora registra desde la grilla del plan; la fecha real es obligatoria
 * y no futura al marcar Realizado y se limpia en los demás estados; las celdas sin permiso de la
 * Bitácora anual no son clicables.
 */
class PermisosBitacoraYFechaRealTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 10, 15));
    }

    /**
     * Plan con frecuencia 4: marzo, junio, septiembre y diciembre programados (RN-01).
     */
    private function plan(?User $responsable = null): PlanMantenimiento
    {
        return PlanMantenimiento::factory()
            ->for(Equipo::factory())
            ->create(['anio' => 2026, 'frecuencia_anual' => FrecuenciaAnual::Cuatro, 'responsable_interno_id' => $responsable?->id]);
    }

    private function grilla(PlanMantenimiento $plan): mixed
    {
        return Livewire::test(EjecucionesMensualesRelationManager::class, ['ownerRecord' => $plan, 'pageClass' => ViewPlanMantenimiento::class]);
    }

    public function test_jefatura_ve_la_grilla_del_plan_pero_no_registra(): void
    {
        $this->actuarComo('Jefatura');
        $plan = $this->plan();

        $this->grilla($plan)
            ->assertSee('Solo lectura.')
            ->assertActionHidden(TestAction::make('editarMes')->arguments(['mes' => 3]));

        $this->assertSame(EstadoEjecucion::Programado, $plan->ejecucionesMensuales()->where('mes', 3)->first()->estado);
    }

    public function test_el_encargado_de_convenios_no_accede_a_la_grilla_del_plan(): void
    {
        $this->actuarComo('Encargado de Convenios');

        $this->assertFalse(EjecucionesMensualesRelationManager::canViewForRecord($this->plan(), ViewPlanMantenimiento::class));
    }

    public function test_el_encargado_y_el_tecnico_asignado_registran_y_el_no_asignado_no(): void
    {
        $tecnico = User::factory()->create();
        $asignado = $this->plan($tecnico);
        $ajeno = $this->plan();

        $this->actuarComo('Encargado de Mantención');
        $this->grilla($ajeno)->assertActionVisible(TestAction::make('editarMes')->arguments(['mes' => 3]));

        $this->actuarComo('Técnico Interno', $tecnico);
        $this->grilla($asignado)
            ->callAction(TestAction::make('editarMes')->arguments(['mes' => 3]), ['estado' => EstadoEjecucion::Realizado->value, 'fecha_real' => '2026-03-12'])
            ->assertHasNoFormErrors();
        $this->grilla($ajeno)->assertActionHidden(TestAction::make('editarMes')->arguments(['mes' => 3]));

        $this->assertSame(EstadoEjecucion::Realizado, $asignado->ejecucionesMensuales()->where('mes', 3)->first()->estado);
    }

    public function test_realizado_exige_fecha_real_y_no_admite_fechas_futuras(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $plan = $this->plan();

        $this->grilla($plan)
            ->callAction(TestAction::make('editarMes')->arguments(['mes' => 3]), ['estado' => EstadoEjecucion::Realizado->value])
            ->assertHasFormErrors(['fecha_real' => 'required']);

        $this->grilla($plan)
            ->callAction(TestAction::make('editarMes')->arguments(['mes' => 3]), ['estado' => EstadoEjecucion::Realizado->value, 'fecha_real' => '2026-12-01'])
            ->assertHasFormErrors(['fecha_real']);

        $this->assertSame(EstadoEjecucion::Programado, $plan->ejecucionesMensuales()->where('mes', 3)->first()->estado);
    }

    public function test_la_fecha_real_se_limpia_fuera_del_estado_realizado(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $marzo = $this->plan()->ejecucionesMensuales()->where('mes', 3)->first();

        $marzo->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => '2026-03-10']);
        $this->assertSame('2026-03-10', $marzo->fresh()->fecha_real->toDateString());

        $marzo->update(['estado' => EstadoEjecucion::Reprogramado, 'observaciones' => 'Repuesto sin stock']);
        $this->assertNull($marzo->fresh()->fecha_real);

        $marzo->update(['estado' => EstadoEjecucion::Programado, 'fecha_real' => '2026-03-10']);
        $this->assertNull($marzo->fresh()->fecha_real);
    }

    public function test_en_la_bitacora_anual_las_celdas_sin_permiso_no_son_clicables(): void
    {
        $tecnico = User::factory()->create();
        $asignado = $this->plan($tecnico);
        $ajeno = $this->plan();
        $this->actuarComo('Técnico Interno', $tecnico);

        $html = Livewire::test(BitacoraAnual::class)->html();

        $this->assertStringContainsString("mountTableAction(&#039;editarMes3&#039;, &#039;{$asignado->id}&#039;)", $html);
        $this->assertStringNotContainsString("mountTableAction(&#039;editarMes3&#039;, &#039;{$ajeno->id}&#039;)", $html);
    }
}
