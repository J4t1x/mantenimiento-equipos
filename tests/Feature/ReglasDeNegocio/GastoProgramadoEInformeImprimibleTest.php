<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Actions\ObtenerDetalleGastoAction;
use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Enums\Periodo;
use App\Exports\DetalleGastoExport;
use App\Filament\Pages\Reportes;
use App\Filament\Resources\Recintos\Pages\EditRecinto;
use App\Filament\Resources\Recintos\RelationManagers\PresupuestosMantenimientoRelationManager;
use App\Models\Equipo;
use App\Models\MantenimientoCorrectivo;
use App\Models\PlanMantenimiento;
use App\Models\PresupuestoMantenimiento;
use App\Models\Recinto;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Módulo 17: RF-76 gasto programado anual por recinto (base del programado en el detalle de gasto)
 * y RF-77 versión imprimible del informe de equipos críticos.
 */
class GastoProgramadoEInformeImprimibleTest extends TestCase
{
    use RefreshDatabase;

    private const ANIO = 2026;

    private Recinto $recinto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(self::ANIO, 9, 15));
        $this->recinto = Recinto::factory()->create(['nombre' => 'Hospital Regional Coyhaique', 'responsable_mp_nombre' => 'Raúl Aravena Turra']);
    }

    public function test_el_detalle_de_gasto_usa_el_gasto_programado_del_recinto_prorrateado(): void
    {
        PresupuestoMantenimiento::factory()->for($this->recinto)->create([
            'anio' => self::ANIO,
            'gasto_programado_mp' => 1_200_000,
            'gasto_programado_mc' => 400_000,
        ]);
        MantenimientoCorrectivo::factory()->for(Equipo::factory()->for($this->recinto))->create(['fecha' => '2026-02-10', 'costo' => 50_000]);

        $detalle = app(ObtenerDetalleGastoAction::class)->execute(self::ANIO, periodo: Periodo::PrimerTrimestre);

        $this->assertSame(300_000.0, $detalle['mp']['programado']);
        $this->assertSame('presupuesto', $detalle['mp']['fuente']);
        $this->assertSame(['programado' => 100_000.0, 'ejecutado' => 50_000.0, 'porcentaje' => 50.0], $detalle['mc']);

        $filaMc = (new DetalleGastoExport(self::ANIO, periodo: Periodo::PrimerTrimestre))->array()[1];
        $this->assertSame([100_000.0, 50_000.0, '50%'], array_slice($filaMc, 1));
    }

    public function test_con_filtro_por_servicio_clinico_no_se_usa_el_presupuesto_del_establecimiento(): void
    {
        PresupuestoMantenimiento::factory()->for($this->recinto)->create(['anio' => self::ANIO]);
        $equipo = Equipo::factory()->for($this->recinto)->create();

        $detalle = app(ObtenerDetalleGastoAction::class)->execute(self::ANIO, servicioClinicoId: $equipo->servicio_clinico_id);

        $this->assertSame('planes', $detalle['mp']['fuente']);
        $this->assertNull($detalle['mc']['programado']);
    }

    public function test_el_gasto_programado_se_registra_desde_la_ficha_del_recinto(): void
    {
        $this->actuarComo('Encargado de Mantención');

        Livewire::test(PresupuestosMantenimientoRelationManager::class, ['ownerRecord' => $this->recinto, 'pageClass' => EditRecinto::class])
            ->callAction(TestAction::make(CreateAction::class)->table(), data: [
                'anio' => self::ANIO,
                'gasto_programado_mp' => 450_000_000,
                'gasto_programado_mc' => 400_000_000,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('400000000.00', $this->recinto->presupuestosMantenimiento()->sole()->gasto_programado_mc);
    }

    public function test_la_version_imprimible_muestra_el_informe_de_la_norma(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $plan = PlanMantenimiento::factory()
            ->for(Equipo::factory()->for($this->recinto)->create(['nombre' => 'Ventilador mecánico']))
            ->create(['anio' => self::ANIO, 'frecuencia_anual' => FrecuenciaAnual::Dos]);
        $plan->ejecucionesMensuales()->where('mes', 6)->first()->update(['estado' => EstadoEjecucion::Reprogramado, 'observaciones' => 'Equipo en uso en UCI', 'documento_justificacion' => 'Memo N° 12']);

        $this->get(route('filament.admin.informe-criticos.imprimir', ['anio' => self::ANIO, 'periodo' => 's1', 'recinto_id' => $this->recinto->id]))
            ->assertOk()
            ->assertSee('Informe de cumplimiento — Mantenimiento preventivo de equipamiento médico crítico')
            ->assertSee('1er semestre (ene–jun) 2026')
            ->assertSee('Raúl Aravena Turra')
            ->assertSee('Ventilador mecánico')
            ->assertSee('Equipo en uso en UCI')
            ->assertSee('Memo N° 12')
            ->assertSee('Unidad de Calidad');
    }

    public function test_la_version_imprimible_exige_permiso_de_reportes_y_respeta_el_recinto_del_usuario(): void
    {
        $otro = Recinto::factory()->create();
        PlanMantenimiento::factory()->for(Equipo::factory()->for($otro)->create(['nombre' => 'Incubadora ajena']))->create(['anio' => self::ANIO]);
        PlanMantenimiento::factory()->for(Equipo::factory()->for($this->recinto)->create(['nombre' => 'Incubadora propia']))->create(['anio' => self::ANIO]);

        $this->actuarComo('Técnico Interno');
        $this->get(route('filament.admin.informe-criticos.imprimir', ['anio' => self::ANIO]))->assertForbidden();

        $encargado = User::factory()->create();
        $encargado->recintos()->attach($this->recinto);
        $this->actuarComo('Encargado de Mantención', $encargado);

        $this->get(route('filament.admin.informe-criticos.imprimir', ['anio' => self::ANIO]))
            ->assertOk()
            ->assertSee('Incubadora propia')
            ->assertDontSee('Incubadora ajena');
    }

    public function test_reportes_enlaza_la_version_imprimible_con_el_alcance_de_la_barra(): void
    {
        $this->actuarComo('Encargado de Mantención');

        Livewire::test(Reportes::class)
            ->set('filtros.periodo', Periodo::PrimerSemestre->value)
            ->set('filtros.recinto_id', $this->recinto->id)
            ->assertActionHasUrl('imprimirInformeCriticos', route('filament.admin.informe-criticos.imprimir', [
                'anio' => self::ANIO,
                'periodo' => 's1',
                'recinto_id' => $this->recinto->id,
            ]));
    }
}
