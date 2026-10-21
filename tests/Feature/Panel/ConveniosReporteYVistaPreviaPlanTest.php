<?php

namespace Tests\Feature\Panel;

use App\Actions\GenerarPlanAnualAction;
use App\Actions\ObtenerConveniosEjecucionAction;
use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Enums\Periodo;
use App\Exports\ConveniosEjecucionExport;
use App\Exports\HojaExport;
use App\Filament\Pages\Reportes;
use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Filament\Resources\Equipos\RelationManagers\PlanesMantenimientoRelationManager;
use App\Filament\Resources\PlanMantenimientos\Pages\CreatePlanMantenimiento;
use App\Filament\Resources\PlanMantenimientos\Pages\EditPlanMantenimiento;
use App\Filament\Support\VistaPreviaPlanAnual;
use App\Models\Convenio;
use App\Models\ConvenioEjecucionMensual;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use App\Models\Recinto;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Excel as ExcelFormato;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RF-82 (tarjeta "Convenios y ejecución" en Reportes) y RF-83 (vista previa de los meses del plan),
 * Módulo 19.
 */
class ConveniosReporteYVistaPreviaPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 10, 15));
    }

    /**
     * Dos convenios activos (uno sobregirado) y uno inactivo con ejecución en 2026.
     */
    private function convenios(): void
    {
        $mantencion = Convenio::factory()->create(['nombre' => 'A · Mantención ventiladores', 'monto_anual' => 1_000_000]);
        ConvenioEjecucionMensual::factory()->for($mantencion)->create(['anio' => 2026, 'mes' => 2, 'monto' => 300_000, 'n_orden_compra' => 'OC-1']);
        ConvenioEjecucionMensual::factory()->for($mantencion)->create(['anio' => 2026, 'mes' => 8, 'monto' => 200_000, 'n_orden_compra' => 'OC-2']);
        ConvenioEjecucionMensual::factory()->for($mantencion)->create(['anio' => 2025, 'mes' => 8, 'monto' => 999_999]);

        $sobregirado = Convenio::factory()->create(['nombre' => 'B · Calibraciones', 'monto_anual' => 100_000]);
        ConvenioEjecucionMensual::factory()->for($sobregirado)->create(['anio' => 2026, 'mes' => 3, 'monto' => 150_000, 'n_orden_compra' => 'OC-3']);

        $inactivo = Convenio::factory()->create(['nombre' => 'C · Antiguo', 'monto_anual' => 50_000, 'activo' => false]);
        ConvenioEjecucionMensual::factory()->for($inactivo)->create(['anio' => 2026, 'mes' => 1, 'monto' => 10_000]);

        Convenio::factory()->create(['nombre' => 'D · Inactivo sin ejecución', 'activo' => false]);
    }

    public function test_calcula_el_resumen_y_el_detalle_de_convenios(): void
    {
        $this->convenios();

        $anual = app(ObtenerConveniosEjecucionAction::class)->execute(2026);
        $semestre = app(ObtenerConveniosEjecucionAction::class)->execute(2026, Periodo::PrimerSemestre);

        $this->assertSame(2, $anual['resumen']['convenios_activos']);
        $this->assertSame(1_100_000.0, $anual['resumen']['monto_comprometido']);
        $this->assertSame(650_000.0, $anual['resumen']['ejecutado_anio']);
        $this->assertSame(59.1, $anual['resumen']['porcentaje']);
        $this->assertSame(1, $anual['resumen']['sobregirados']);
        $this->assertSame(['A · Mantención ventiladores', 'B · Calibraciones', 'C · Antiguo'], $anual['convenios']->pluck('convenio')->all());
        $this->assertSame(50.0, $anual['convenios'][0]['porcentaje']);
        $this->assertCount(4, $anual['detalle']);

        $this->assertSame(450_000.0, $semestre['resumen']['ejecutado_periodo']);
        $this->assertSame(650_000.0, $semestre['resumen']['ejecutado_anio']);
        $this->assertSame(300_000.0, $semestre['convenios'][0]['ejecutado_periodo']);
        $this->assertCount(3, $semestre['detalle']);
    }

    public function test_genera_un_xlsx_real_con_resumen_y_detalle_mensual(): void
    {
        $this->convenios();

        $archivo = tempnam(sys_get_temp_dir(), 'convenios').'.xlsx';
        file_put_contents($archivo, Excel::raw(new ConveniosEjecucionExport(2026), ExcelFormato::XLSX));
        $libro = IOFactory::load($archivo);

        $this->assertSame(['Resumen', 'Detalle mensual'], $libro->getSheetNames());
        $this->assertSame('A · Mantención ventiladores', $libro->getSheetByName('Resumen')->getCell('B2')->getValue());
        $this->assertSame('Sí', $libro->getSheetByName('Resumen')->getCell('J3')->getValue());
        $this->assertSame('Febrero', $libro->getSheetByName('Detalle mensual')->getCell('C2')->getValue());
        $this->assertSame('OC-1', $libro->getSheetByName('Detalle mensual')->getCell('D2')->getValue());

        unlink($archivo);
    }

    public function test_reportes_muestra_la_tarjeta_y_descarga_sin_acotar_por_recinto(): void
    {
        Excel::fake();
        $this->convenios();
        $this->actuarComo('Encargado de Convenios');
        $recinto = Recinto::factory()->create();

        Livewire::test(Reportes::class)
            ->assertSee('Convenios y ejecución')
            ->assertSee('59.1%')
            ->assertSee('1 convenio sobregirado')
            ->set('filtros.recinto_id', $recinto->id)
            ->set('filtros.periodo', Periodo::PrimerSemestre->value)
            ->assertSee('Ejecutado en el 1er semestre de 2026: $450.000')
            ->callAction('descargarConvenios');

        Excel::assertDownloaded('convenios-ejecucion-2026-s1.xlsx', function (ConveniosEjecucionExport $export): bool {
            $hojas = $export->sheets();

            return array_map(fn (HojaExport $hoja): string => $hoja->title(), $hojas) === ['Resumen', 'Detalle mensual']
                && count($hojas[0]->array()) === 3
                && count($hojas[1]->array()) === 3;
        });
    }

    /**
     * @return array<string, array{FrecuenciaAnual}>
     */
    public static function frecuencias(): array
    {
        return collect(FrecuenciaAnual::cases())->mapWithKeys(fn (FrecuenciaAnual $frecuencia): array => [$frecuencia->getLabel() => [$frecuencia]])->all();
    }

    #[DataProvider('frecuencias')]
    public function test_la_vista_previa_usa_los_mismos_meses_que_la_generacion_del_plan(FrecuenciaAnual $frecuencia): void
    {
        $plan = PlanMantenimiento::factory()->for(Equipo::factory()->relevante())->create(['frecuencia_anual' => $frecuencia]);
        app(GenerarPlanAnualAction::class)->execute($plan);

        $generados = $plan->ejecucionesMensuales()->where('estado', EstadoEjecucion::Programado)->orderBy('mes')->pluck('mes')->all();

        $this->assertSame($frecuencia->mesesProgramados(), $generados);
        $this->assertCount((int) $frecuencia->value, $generados);
    }

    public function test_el_formulario_del_plan_muestra_la_vista_previa_al_elegir_la_frecuencia(): void
    {
        $this->actuarComo('Encargado de Mantención');

        Livewire::test(CreatePlanMantenimiento::class)
            ->assertSee('Elige la frecuencia anual para ver los meses.')
            ->fillForm(['frecuencia_anual' => FrecuenciaAnual::Cuatro->value])
            ->assertSee('Marzo, junio, septiembre y diciembre (4 mantenciones)')
            ->fillForm(['frecuencia_anual' => FrecuenciaAnual::Una->value])
            ->assertSee('Diciembre (1 mantención)');
    }

    public function test_al_editar_advierte_los_meses_que_se_conservan(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $plan = PlanMantenimiento::factory()->create(['frecuencia_anual' => FrecuenciaAnual::Cuatro]);
        $plan->ejecucionesMensuales()->where('mes', 3)->first()->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => '2026-03-10']);

        Livewire::test(EditPlanMantenimiento::class, ['record' => $plan->getRouteKey()])
            ->assertSee('Marzo, junio, septiembre y diciembre (4 mantenciones)')
            ->assertSee('se conservan los meses ya registrados: marzo (realizado).');
    }

    public function test_el_relation_manager_del_equipo_tambien_muestra_la_vista_previa(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $equipo = Equipo::factory()->create();

        // En la página de edición: en la de vista el relation manager es de solo lectura.
        Livewire::test(PlanesMantenimientoRelationManager::class, ['ownerRecord' => $equipo, 'pageClass' => EditEquipo::class])
            ->mountAction(TestAction::make('create')->table())
            ->assertSchemaComponentExists('vista_previa_meses')
            ->fillForm(['frecuencia_anual' => FrecuenciaAnual::Seis->value])
            ->assertSchemaStateSet(['vista_previa_meses' => 'Febrero, abril, junio, agosto, octubre y diciembre (6 mantenciones)']);

        $this->assertSame('Febrero, abril, junio, agosto, octubre y diciembre (6 mantenciones)', VistaPreviaPlanAnual::descripcion(FrecuenciaAnual::Seis));
    }
}
