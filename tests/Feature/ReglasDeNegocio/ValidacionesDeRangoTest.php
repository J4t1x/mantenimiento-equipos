<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Enums\FrecuenciaAnual;
use App\Enums\TipoMantenimiento;
use App\Exceptions\ValorFueraDeRangoException;
use App\Filament\Resources\Convenios\Pages\CreateConvenio;
use App\Filament\Resources\Convenios\Pages\ViewConvenio;
use App\Filament\Resources\Convenios\RelationManagers\EjecucionesMensualesRelationManager;
use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Filament\Resources\Equipos\Pages\ViewEquipo;
use App\Filament\Resources\MantenimientoCorrectivos\Pages\CreateMantenimientoCorrectivo;
use App\Filament\Resources\PlanMantenimientos\Pages\CreatePlanMantenimiento;
use App\Models\Convenio;
use App\Models\ConvenioEjecucionMensual;
use App\Models\Equipo;
use App\Models\MantenimientoCorrectivo;
use App\Models\PlanMantenimiento;
use App\Models\Proveedor;
use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SPEC-ME-015 (hallazgos M-01, M-04 y B-02 de la prueba funcional del 03-11-2026): rangos en los
 * formularios y en el modelo, registro de la ejecución mensual del convenio desde su ficha y
 * etiquetas de los botones del retiro de uso.
 */
class ValidacionesDeRangoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 10, 15));
    }

    public function test_el_correctivo_rechaza_costo_negativo_y_fecha_de_falla_futura(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $equipo = Equipo::factory()->create();

        Livewire::test(CreateMantenimientoCorrectivo::class)
            ->fillForm([
                'equipo_id' => $equipo->id,
                'fecha' => '2027-01-10',
                'falla_descripcion' => 'No enciende',
                'costo' => -5000,
            ])
            ->call('create')
            ->assertHasFormErrors(['fecha', 'costo'])
            ->assertSee('La fecha no puede ser posterior a hoy.')
            ->assertSee('debe ser al menos 0');

        $this->assertSame(0, MantenimientoCorrectivo::count());
    }

    public function test_el_plan_rechaza_un_anio_fuera_de_rango_y_costo_negativo(): void
    {
        $this->actuarComo('Encargado de Mantención');

        Livewire::test(CreatePlanMantenimiento::class)
            ->fillForm([
                'equipo_id' => Equipo::factory()->create()->id,
                'anio' => 1500,
                'frecuencia_anual' => FrecuenciaAnual::Cuatro,
                'tipo_mantenimiento' => TipoMantenimiento::Externo,
                'costo_anual_referencia' => -1,
            ])
            ->call('create')
            ->assertHasFormErrors(['anio' => 'min', 'costo_anual_referencia' => 'min']);

        $this->assertSame(0, PlanMantenimiento::count());
    }

    public function test_el_equipo_rechaza_vida_util_cero_y_anios_fuera_de_rango(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $equipo = Equipo::factory()->create();

        Livewire::test(EditEquipo::class, ['record' => $equipo->getRouteKey()])
            ->fillForm([
                'vida_util_anios' => 0,
                'anio_adquisicion' => 1500,
                'en_garantia' => true,
                'garantia_anio_vencimiento' => 4692,
            ])
            ->call('save')
            ->assertHasFormErrors(['vida_util_anios' => 'min', 'anio_adquisicion' => 'min', 'garantia_anio_vencimiento' => 'max']);

        Livewire::test(EditEquipo::class, ['record' => $equipo->getRouteKey()])
            ->fillForm(['vida_util_anios' => 10, 'anio_adquisicion' => 2015, 'en_garantia' => true, 'garantia_anio_vencimiento' => 2028])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_el_convenio_rechaza_expiracion_anterior_a_la_resolucion_y_monto_negativo(): void
    {
        $this->actuarComo('Encargado de Convenios');

        Livewire::test(CreateConvenio::class)
            ->fillForm([
                'proveedor_id' => Proveedor::factory()->create()->id,
                'nombre' => 'Mantención de autoclaves',
                'fecha_resolucion' => '2026-03-01',
                'fecha_expiracion' => '2026-01-31',
                'monto_anual' => -100,
            ])
            ->call('create')
            ->assertHasFormErrors(['fecha_expiracion' => 'after_or_equal', 'monto_anual' => 'min'])
            ->assertSee('posterior o igual');

        $this->assertSame(0, Convenio::count());
    }

    public function test_el_modelo_rechaza_montos_negativos_y_fechas_de_falla_futuras_por_cualquier_via(): void
    {
        $casos = [
            fn () => MantenimientoCorrectivo::factory()->create(['costo' => -1]),
            fn () => MantenimientoCorrectivo::factory()->create(['fecha' => '2026-12-01']),
            fn () => Convenio::factory()->create(['monto_anual' => -1]),
            fn () => ConvenioEjecucionMensual::factory()->create(['monto' => -1]),
            fn () => PlanMantenimiento::factory()->create(['costo_anual_referencia' => -1]),
        ];

        foreach ($casos as $indice => $caso) {
            try {
                $caso();
                $this->fail("El caso {$indice} debió rechazarse.");
            } catch (ValorFueraDeRangoException $excepcion) {
                $this->assertStringContainsString('no puede ser', $excepcion->getMessage());
            }
        }

        $this->assertSame(0, MantenimientoCorrectivo::count());
        $this->assertNotNull(MantenimientoCorrectivo::factory()->create(['costo' => 0, 'fecha' => today()]));
    }

    public function test_la_ejecucion_mensual_se_registra_desde_la_ficha_ver_del_convenio(): void
    {
        $convenio = Convenio::factory()->create();
        $relationManager = fn () => Livewire::test(EjecucionesMensualesRelationManager::class, ['ownerRecord' => $convenio, 'pageClass' => ViewConvenio::class]);

        $this->actuarComo('Encargado de Convenios');
        $relationManager()
            ->assertActionVisible(TestAction::make('create')->table())
            ->assertActionHasLabel(TestAction::make('create')->table(), 'Registrar ejecución mensual')
            ->callAction(TestAction::make('create')->table(), [
                'anio' => 2026,
                'mes' => 9,
                'n_orden_compra' => 'OC 1234',
                'monto' => 250_000,
            ])
            ->assertHasNoFormErrors();
        $this->assertSame(1, $convenio->ejecucionesMensuales()->count());

        $this->actuarComo('Jefatura');
        $relationManager()->assertActionHidden(TestAction::make('create')->table());
    }

    public function test_los_botones_del_retiro_de_uso_y_del_reingreso_dicen_lo_que_hacen(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $equipo = Equipo::factory()->create();

        Livewire::test(ViewEquipo::class, ['record' => $equipo->getRouteKey()])
            ->assertActionExists('retirarDeUso', fn (Action $action): bool => $action->getModalSubmitActionLabel() === 'Retirar de uso')
            ->assertActionExists('registrarReingreso', fn (Action $action): bool => $action->getModalSubmitActionLabel() === 'Registrar reingreso');
    }

    public function test_el_importador_lee_las_garantias_con_fecha_en_formato_de_excel(): void
    {
        $this->artisan('app:importar-catastro-coyhaique')->assertSuccessful();

        $this->assertLessThanOrEqual(2028, Equipo::max('garantia_anio_vencimiento'));
        $this->assertSame(2028, Equipo::where('n_inventario', '19728')->value('garantia_anio_vencimiento'));
        $this->assertSame(2025, Equipo::where('n_inventario', '18955')->value('garantia_anio_vencimiento'));
    }
}
