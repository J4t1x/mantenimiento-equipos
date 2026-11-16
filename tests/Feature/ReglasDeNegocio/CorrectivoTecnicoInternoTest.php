<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Exceptions\EquipoNoAsignadoException;
use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Filament\Resources\Equipos\RelationManagers\MantenimientosCorrectivosRelationManager;
use App\Models\Equipo;
use App\Models\MantenimientoCorrectivo;
use App\Models\PlanMantenimiento;
use App\Models\User;
use App\Support\AlcanceTecnicoInterno;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SPEC-ME-022: el Técnico Interno registra y edita correctivos solo de los equipos de los que es
 * responsable interno en algún plan (misma regla que RF-20 para la bitácora). El Encargado de
 * Mantención los registra para todos los equipos de su establecimiento.
 */
class CorrectivoTecnicoInternoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Equipo, 2: Equipo}
     */
    private function tecnicoConEquipos(): array
    {
        $tecnico = User::factory()->create();
        $asignado = PlanMantenimiento::factory()->create(['responsable_interno_id' => $tecnico->id])->equipo;
        $ajeno = PlanMantenimiento::factory()->create()->equipo;
        $this->actuarComo('Técnico Interno', $tecnico);

        return [$tecnico, $asignado, $ajeno];
    }

    public function test_el_tecnico_registra_correctivos_de_sus_equipos_asignados(): void
    {
        [, $asignado] = $this->tecnicoConEquipos();

        $correctivo = MantenimientoCorrectivo::factory()->create(['equipo_id' => $asignado->id]);

        $this->assertTrue($correctivo->exists);
    }

    public function test_el_tecnico_no_registra_correctivos_de_equipos_no_asignados(): void
    {
        [, , $ajeno] = $this->tecnicoConEquipos();

        $this->expectException(EquipoNoAsignadoException::class);
        $this->expectExceptionMessage('No puedes registrar un correctivo de un equipo que no tienes asignado');

        MantenimientoCorrectivo::factory()->create(['equipo_id' => $ajeno->id]);
    }

    public function test_el_tecnico_no_edita_ni_traslada_correctivos_de_equipos_no_asignados(): void
    {
        $tecnico = User::factory()->create();
        $asignado = PlanMantenimiento::factory()->create(['responsable_interno_id' => $tecnico->id])->equipo;
        $ajeno = PlanMantenimiento::factory()->create()->equipo;
        $propio = MantenimientoCorrectivo::factory()->create(['equipo_id' => $asignado->id]);
        $deOtro = MantenimientoCorrectivo::factory()->create(['equipo_id' => $ajeno->id]);
        $this->actuarComo('Técnico Interno', $tecnico);

        $this->assertTrue($tecnico->can('update', $propio));
        $this->assertFalse($tecnico->can('update', $deOtro));

        $this->expectException(EquipoNoAsignadoException::class);
        $propio->update(['equipo_id' => $ajeno->id]);
    }

    public function test_el_formulario_solo_ofrece_al_tecnico_sus_equipos_asignados(): void
    {
        [, $asignado, $ajeno] = $this->tecnicoConEquipos();

        $ids = AlcanceTecnicoInterno::limitarEquipos(Equipo::query())->pluck('id');

        $this->assertTrue($ids->contains($asignado->id));
        $this->assertFalse($ids->contains($ajeno->id));
    }

    public function test_la_ficha_del_equipo_solo_permite_al_tecnico_crear_correctivos_si_lo_tiene_asignado(): void
    {
        [, $asignado, $ajeno] = $this->tecnicoConEquipos();

        Livewire::test(MantenimientosCorrectivosRelationManager::class, ['ownerRecord' => $asignado, 'pageClass' => EditEquipo::class])
            ->assertActionVisible(TestAction::make('create')->table());

        Livewire::test(MantenimientosCorrectivosRelationManager::class, ['ownerRecord' => $ajeno, 'pageClass' => EditEquipo::class])
            ->assertActionHidden(TestAction::make('create')->table());
    }

    public function test_el_encargado_de_mantencion_registra_correctivos_de_cualquier_equipo(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $equipo = PlanMantenimiento::factory()->create()->equipo;

        $correctivo = MantenimientoCorrectivo::factory()->create(['equipo_id' => $equipo->id]);

        $this->assertTrue($correctivo->exists);
        $this->assertTrue(AlcanceTecnicoInterno::limitarEquipos(Equipo::query())->whereKey($equipo->id)->exists());
    }
}
