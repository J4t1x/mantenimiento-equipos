<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Actions\ObtenerInformeCriticosAction;
use App\Enums\Criticidad;
use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Enums\Periodo;
use App\Enums\TipoEquipoCritico;
use App\Exceptions\TipoCriticoSinCriticidadException;
use App\Filament\Pages\Reportes;
use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Filament\Resources\Recintos\Pages\EditRecinto;
use App\Filament\Resources\Recintos\RelationManagers\ProgramasAnualesMantenimientoRelationManager;
use App\Filament\Widgets\CatastroAlertasWidget;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use App\Models\PresupuestoMantenimiento;
use App\Models\ProgramaAnualMantenimiento;
use App\Models\Recinto;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * RF-72 programa anual definido y validado (Res. Ex. 1341/2017 §7.2–7.3), RF-75 tipos de equipo
 * crítico de la norma ("Definiciones") y RF-78 informe imprimible de cumplimiento y gasto.
 */
class ProgramaAnualTiposEInformeCumplimientoTest extends TestCase
{
    use RefreshDatabase;

    private const ANIO = 2026;

    private Recinto $recinto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(self::ANIO, 9, 15));
        $this->recinto = Recinto::factory()->create(['nombre' => 'Hospital Regional Coyhaique']);
    }

    /**
     * @return array<int, mixed>
     */
    private function statsDelWidget(): array
    {
        $widget = new CatastroAlertasWidget;

        return (new ReflectionMethod($widget, 'getStats'))->invoke($widget);
    }

    /**
     * @return array<string, array{string, TipoEquipoCritico|null}>
     */
    public static function nombres(): array
    {
        return [
            'desfibrilador' => ['Monitor Desfibrilador DEA', TipoEquipoCritico::MonitorDesfibrilador],
            'ventilador de transporte' => ['Ventilador de transporte', TipoEquipoCritico::VentiladorMecanico],
            'incubadora' => ['Incubadora intensivo', TipoEquipoCritico::Incubadora],
            'anestesia' => ['Máquina de anestesia', TipoEquipoCritico::MaquinaAnestesia],
            'diálisis' => ['Monitor Diálisis N° 3', TipoEquipoCritico::MaquinaDialisis],
            'planta de diálisis no es máquina' => ['Planta de tratamiento de dialisis', null],
            'multiparámetros se clasifica a mano' => ['Monitor multiparametros', null],
        ];
    }

    #[DataProvider('nombres')]
    public function test_sugiere_el_tipo_de_la_norma_por_nombre(string $nombre, ?TipoEquipoCritico $esperado): void
    {
        $this->assertSame($esperado, TipoEquipoCritico::sugerirPorNombre($nombre));
    }

    public function test_un_tipo_de_la_norma_exige_criticidad_critico(): void
    {
        Equipo::factory()->create(['tipo_critico_norma' => TipoEquipoCritico::VentiladorMecanico]);
        Equipo::factory()->relevante()->create(['tipo_critico_norma' => TipoEquipoCritico::NoCorresponde]);

        $this->expectException(TipoCriticoSinCriticidadException::class);

        Equipo::factory()->relevante()->create(['tipo_critico_norma' => TipoEquipoCritico::MaquinaDialisis]);
    }

    public function test_el_formulario_de_equipo_muestra_el_error(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $equipo = Equipo::factory()->relevante()->create();

        Livewire::test(EditEquipo::class, ['record' => $equipo->getRouteKey()])
            ->fillForm(['tipo_critico_norma' => TipoEquipoCritico::Incubadora->value])
            ->call('save')
            ->assertHasFormErrors(['criticidad']);

        $this->assertNull($equipo->fresh()->tipo_critico_norma);
    }

    public function test_el_comando_asigna_sin_conflicto_y_reporta_inconsistencias_sin_cambiar_criticidad(): void
    {
        $ventilador = Equipo::factory()->create(['nombre' => 'Ventilador adulto']);
        $dialisis = Equipo::factory()->relevante()->create(['nombre' => 'Monitor Dialisis N° 1']);
        $multiparametros = Equipo::factory()->create(['nombre' => 'Monitor multiparametros']);

        $this->artisan('app:clasificar-tipos-criticos')->assertSuccessful();
        $this->assertNull($ventilador->fresh()->tipo_critico_norma);

        $this->artisan('app:clasificar-tipos-criticos', ['--aplicar' => true])
            ->expectsOutputToContain('requieren revisión del encargado')
            ->assertSuccessful();

        $this->assertSame(TipoEquipoCritico::VentiladorMecanico, $ventilador->fresh()->tipo_critico_norma);
        $this->assertNull($dialisis->fresh()->tipo_critico_norma);
        $this->assertSame(Criticidad::Relevante, $dialisis->fresh()->criticidad);
        $this->assertNull($multiparametros->fresh()->tipo_critico_norma);

        $tipos = app(ObtenerInformeCriticosAction::class)->execute(self::ANIO, Periodo::Anual)['tipos_norma'];
        $this->assertSame(1, $tipos[TipoEquipoCritico::VentiladorMecanico->getLabel()]);
        $this->assertSame(1, $tipos['Sin clasificar']);
    }

    public function test_el_programa_anual_registra_plazo_y_validacion(): void
    {
        $tarde = ProgramaAnualMantenimiento::factory()->for($this->recinto)->sinValidar()->create(['fecha_definicion' => '2026-04-10']);

        $this->assertFalse($tarde->definidoEnPlazo());
        $this->assertSame('definido el 10-04-2026 (fuera del plazo de marzo); sin validación de la Dirección', $tarde->descripcion());

        $tarde->update(['fecha_validacion' => '2026-04-20', 'validado_por_nombre' => 'Ana Soto', 'validado_por_cargo' => 'Directora', 'documento_validacion' => 'Res. Ex. N° 77']);

        $this->assertSame(
            'Definido el 10-04-2026 (fuera del plazo de marzo); validado el 20-04-2026 por Ana Soto (Directora); Res. Ex. N° 77',
            app(ObtenerInformeCriticosAction::class)->execute(self::ANIO, Periodo::Anual, $this->recinto->id)['programa_anual'],
        );
    }

    public function test_desde_abril_alerta_recintos_sin_programa_validado(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $validado = Recinto::factory()->create();
        ProgramaAnualMantenimiento::factory()->for($validado)->create();
        ProgramaAnualMantenimiento::factory()->for($this->recinto)->sinValidar()->create();

        $alerta = $this->statsDelWidget()[5];

        $this->assertSame(1, $alerta->getValue());
        $this->assertStringContainsString('filters[programa_sin_validar][isActive]=1', urldecode($alerta->getUrl()));

        $this->travelTo(now()->setDate(self::ANIO, 2, 10));
        $this->assertCount(5, $this->statsDelWidget());
    }

    public function test_el_programa_se_registra_desde_la_ficha_del_recinto(): void
    {
        $this->actuarComo('Encargado de Mantención');

        Livewire::test(ProgramasAnualesMantenimientoRelationManager::class, ['ownerRecord' => $this->recinto, 'pageClass' => EditRecinto::class])
            ->callAction(TestAction::make(CreateAction::class)->table(), data: [
                'anio' => self::ANIO,
                'fecha_definicion' => '2026-03-20',
                'fecha_validacion' => '2026-03-30',
                'validado_por_nombre' => 'Ana Soto',
                'documento_validacion' => 'Res. Ex. N° 77',
            ])
            ->assertHasNoActionErrors();

        $this->assertTrue($this->recinto->programasAnualesMantenimiento()->sole()->estaValidado());
    }

    public function test_el_informe_imprimible_de_cumplimiento_y_gasto(): void
    {
        $plan = PlanMantenimiento::factory()
            ->for(Equipo::factory()->for($this->recinto))
            ->create(['anio' => self::ANIO, 'frecuencia_anual' => FrecuenciaAnual::Cuatro]);
        $plan->ejecucionesMensuales()->where('mes', 3)->first()->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => now()]);
        PresupuestoMantenimiento::factory()->for($this->recinto)->create(['anio' => self::ANIO, 'gasto_programado_mp' => 1_200_000, 'gasto_programado_mc' => 400_000]);

        $this->actuarComo('Técnico Interno');
        $this->get(route('filament.admin.informe-cumplimiento.imprimir', ['anio' => self::ANIO]))->assertForbidden();

        $this->actuarComo('Encargado de Mantención');
        $this->get(route('filament.admin.informe-cumplimiento.imprimir', ['anio' => self::ANIO, 'periodo' => 't1', 'recinto_id' => $this->recinto->id]))
            ->assertOk()
            ->assertSee('Informe de cumplimiento y gasto de mantenimiento de equipos médicos')
            ->assertSee('1er trimestre (ene–mar) 2026')
            ->assertSee('100,0%')
            ->assertSee('$300.000')
            ->assertSee('$100.000')
            ->assertSee('gasto programado anual del establecimiento');

        Livewire::test(Reportes::class)
            ->set('filtros.periodo', Periodo::CuartoTrimestre->value)
            ->assertActionHasUrl('imprimirInformeCumplimiento', route('filament.admin.informe-cumplimiento.imprimir', ['anio' => self::ANIO, 'periodo' => 't4']));
    }
}
