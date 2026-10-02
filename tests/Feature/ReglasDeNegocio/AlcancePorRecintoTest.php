<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Actions\CalcularCumplimientoMpAction;
use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Exceptions\RecintoFueraDeAlcanceException;
use App\Filament\Resources\Equipos\EquipoResource;
use App\Filament\Resources\Equipos\Pages\ListEquipos;
use App\Filament\Resources\Recintos\RecintoResource;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\EjecucionMensual;
use App\Models\Equipo;
use App\Models\MantenimientoCorrectivo;
use App\Models\PlanMantenimiento;
use App\Models\Recinto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * RF-63: un usuario con recintos asignados solo ve y registra datos de esos recintos; uno sin
 * recintos asignados (subdepartamento del SSA) ve todos. Se valida en las consultas (global scope
 * e indicadores con `DB::table()`), en las guardas de modelo (RNF-04) y en el panel.
 */
class AlcancePorRecintoTest extends TestCase
{
    use RefreshDatabase;

    private Recinto $propio;

    private Recinto $ajeno;

    private Equipo $equipoPropio;

    private Equipo $equipoAjeno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->propio = Recinto::factory()->create();
        $this->ajeno = Recinto::factory()->create();

        foreach ([$this->propio, $this->ajeno] as $recinto) {
            $equipo = Equipo::factory()->for($recinto)->create();
            $plan = PlanMantenimiento::factory()->for($equipo)->create(['frecuencia_anual' => FrecuenciaAnual::Cuatro]);
            $plan->ejecucionesMensuales()->where('mes', 3)->first()->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => now()]);
            MantenimientoCorrectivo::factory()->for($equipo)->create();
        }

        $this->equipoPropio = $this->propio->equipos()->sole();
        $this->equipoAjeno = $this->ajeno->equipos()->sole();
    }

    private function actuarComoEncargadoDe(Recinto ...$recintos): User
    {
        $usuario = User::factory()->create();
        $usuario->recintos()->attach(collect($recintos)->pluck('id'));

        return $this->actuarComo('Encargado de Mantención', $usuario);
    }

    public function test_un_usuario_sin_recintos_asignados_ve_todos(): void
    {
        $this->actuarComo('Encargado de Mantención');

        $this->assertSame(2, Recinto::count());
        $this->assertSame(2, Equipo::count());
        $this->assertSame(2, PlanMantenimiento::count());
        $this->assertSame(24, EjecucionMensual::count());
        $this->assertSame(2, MantenimientoCorrectivo::count());
    }

    public function test_un_encargado_con_recinto_solo_ve_los_datos_de_su_recinto(): void
    {
        $this->actuarComoEncargadoDe($this->propio);

        $this->assertSame([$this->propio->id], Recinto::pluck('id')->all());
        $this->assertSame([$this->equipoPropio->id], Equipo::pluck('id')->all());
        $this->assertSame([$this->equipoPropio->id], PlanMantenimiento::pluck('equipo_id')->all());
        $this->assertSame(12, EjecucionMensual::count());
        $this->assertSame([$this->equipoPropio->id], MantenimientoCorrectivo::pluck('equipo_id')->all());
    }

    public function test_los_indicadores_de_cumplimiento_se_acotan_al_recinto(): void
    {
        $this->actuarComoEncargadoDe($this->propio);
        $accion = app(CalcularCumplimientoMpAction::class);

        $this->assertSame(4, $accion->execute(now()->year)['total']['programados']);
        $this->assertSame(1, $accion->porMes(now()->year)[3]['programados']);
    }

    public function test_no_puede_registrar_datos_en_un_recinto_ajeno(): void
    {
        $this->actuarComoEncargadoDe($this->propio);

        Equipo::factory()->for($this->propio)->create();

        $this->expectException(RecintoFueraDeAlcanceException::class);
        Equipo::factory()->for($this->ajeno)->create();
    }

    public function test_no_puede_registrar_un_correctivo_de_un_equipo_ajeno(): void
    {
        $this->actuarComoEncargadoDe($this->propio);

        $this->expectException(RecintoFueraDeAlcanceException::class);
        MantenimientoCorrectivo::factory()->create(['equipo_id' => $this->equipoAjeno->id]);
    }

    public function test_en_el_panel_solo_lista_y_abre_equipos_de_su_recinto(): void
    {
        $this->actuarComoEncargadoDe($this->propio);

        Livewire::test(ListEquipos::class)
            ->assertCanSeeTableRecords([$this->equipoPropio])
            ->assertCanNotSeeTableRecords([$this->equipoAjeno]);

        $this->get(EquipoResource::getUrl('view', ['record' => $this->equipoPropio]))->assertOk();
        $this->get(EquipoResource::getUrl('view', ['record' => $this->equipoAjeno]))->assertNotFound();
    }

    public function test_solo_el_subdepartamento_administra_el_catalogo_de_recintos(): void
    {
        $this->actuarComoEncargadoDe($this->propio);
        $this->get(RecintoResource::getUrl('create'))->assertForbidden();

        $this->actuarComo('Encargado de Mantención');
        $this->get(RecintoResource::getUrl('create'))->assertOk();
    }

    public function test_el_administrador_asigna_recintos_desde_la_ficha_del_usuario(): void
    {
        $usuario = User::factory()->create();
        $this->actuarComo('super_admin');

        Livewire::test(EditUser::class, ['record' => $usuario->getRouteKey()])
            ->fillForm(['recintos' => [$this->propio->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([$this->propio->id], $usuario->fresh()->recintoIdsAsignados());
    }
}
