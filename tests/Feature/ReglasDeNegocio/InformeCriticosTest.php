<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Actions\ObtenerInformeCriticosAction;
use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Enums\Periodo;
use App\Exports\HojaExport;
use App\Exports\InformeCriticosExport;
use App\Filament\Pages\Reportes;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use App\Models\Recinto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Excel as ExcelFormato;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * RF-68 (Res. Ex. 1341/2017 §8): informe de cumplimiento de equipos críticos por período, con el
 * indicador por equipos (no por mantenciones) y las reprogramaciones con su causa, incluidas las
 * que ya se realizaron.
 */
class InformeCriticosTest extends TestCase
{
    use RefreshDatabase;

    private const ANIO = 2026;

    private Recinto $otroRecinto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(self::ANIO, 9, 15));
        $this->otroRecinto = Recinto::factory()->create();

        // A (otro recinto): semestral, junio realizado → cumple el 1er semestre.
        $a = $this->plan(Equipo::factory()->for($this->otroRecinto)->create(['nombre' => 'Ventilador A']), FrecuenciaAnual::Dos);
        $this->marcar($a, 6, EstadoEjecucion::Realizado);

        // B: trimestral, marzo realizado y junio reprogramado sin realizar → no cumple.
        $b = $this->plan(Equipo::factory()->create(['nombre' => 'Incubadora B']), FrecuenciaAnual::Cuatro);
        $this->marcar($b, 3, EstadoEjecucion::Realizado);
        $this->marcar($b, 6, EstadoEjecucion::Reprogramado, 'Equipo en uso continuo');

        // C: trimestral, marzo reprogramado y luego realizado; junio sin realizar → no cumple.
        $c = $this->plan(Equipo::factory()->create(['nombre' => 'Monitor C']), FrecuenciaAnual::Cuatro);
        $this->marcar($c, 3, EstadoEjecucion::Reprogramado, 'Repuesto sin stock');
        $this->marcar($c, 3, EstadoEjecucion::Realizado, 'Realizado por el proveedor');

        // D: relevante → fuera del informe.
        $d = $this->plan(Equipo::factory()->relevante()->create(), FrecuenciaAnual::Dos);
        $this->marcar($d, 6, EstadoEjecucion::Realizado);
    }

    private function plan(Equipo $equipo, FrecuenciaAnual $frecuencia): PlanMantenimiento
    {
        return PlanMantenimiento::factory()->for($equipo)->create(['anio' => self::ANIO, 'frecuencia_anual' => $frecuencia]);
    }

    private function marcar(PlanMantenimiento $plan, int $mes, EstadoEjecucion $estado, ?string $observaciones = null): void
    {
        $plan->ejecucionesMensuales()->where('mes', $mes)->firstOrFail()->update([
            'estado' => $estado,
            'observaciones' => $observaciones,
            'fecha_real' => $estado === EstadoEjecucion::Realizado ? now() : null,
        ]);
    }

    public function test_indicador_por_equipos_criticos_del_primer_semestre(): void
    {
        $informe = app(ObtenerInformeCriticosAction::class)->execute(self::ANIO, Periodo::PrimerSemestre);

        $this->assertSame([
            'equipos_criticos' => 3,
            'criticos_sin_plan' => 0,
            'con_mp_programada' => 3,
            'con_mp_ejecutada' => 1,
            'porcentaje' => 33.3,
            'mantenciones_programadas' => 5,
            'mantenciones_realizadas' => 3,
            'reprogramaciones' => 2,
        ], $informe['resumen']);

        $this->assertSame(
            ['Incubadora B' => false, 'Monitor C' => false, 'Ventilador A' => true],
            $informe['equipos']->sortBy('equipo')->pluck('cumple', 'equipo')->all(),
        );
    }

    public function test_en_el_anio_completo_ningun_equipo_tiene_todas_sus_mp_realizadas(): void
    {
        $resumen = app(ObtenerInformeCriticosAction::class)->execute(self::ANIO, Periodo::Anual)['resumen'];

        $this->assertSame(0, $resumen['con_mp_ejecutada']);
        $this->assertSame(0.0, $resumen['porcentaje']);
    }

    public function test_las_reprogramaciones_conservan_su_causa_aunque_ya_se_hayan_realizado(): void
    {
        $reprogramaciones = app(ObtenerInformeCriticosAction::class)
            ->execute(self::ANIO, Periodo::PrimerSemestre)['reprogramaciones']
            ->keyBy('equipo');

        $this->assertSame('Repuesto sin stock', $reprogramaciones['Monitor C']['causa']);
        $this->assertSame(EstadoEjecucion::Realizado, $reprogramaciones['Monitor C']['estado']);
        $this->assertFalse($reprogramaciones['Monitor C']['vencida']);

        $this->assertSame('Equipo en uso continuo', $reprogramaciones['Incubadora B']['causa']);
        $this->assertSame('30-07-2026', $reprogramaciones['Incubadora B']['plazo']);
        $this->assertTrue($reprogramaciones['Incubadora B']['vencida']);
    }

    public function test_se_acota_por_recinto(): void
    {
        $resumen = app(ObtenerInformeCriticosAction::class)->execute(self::ANIO, Periodo::PrimerSemestre, $this->otroRecinto->id)['resumen'];

        $this->assertSame(1, $resumen['equipos_criticos']);
        $this->assertSame(100.0, $resumen['porcentaje']);
        $this->assertSame(0, $resumen['reprogramaciones']);
    }

    /**
     * RF-69: un crítico en garantía con frecuencia 1 figura con periodicidad de fabricante.
     * RF-70: el resumen muestra al responsable de MP del recinto filtrado.
     */
    public function test_el_informe_indica_periodicidad_de_fabricante_y_responsable_de_mp(): void
    {
        $this->otroRecinto->update(['responsable_mp_nombre' => 'Ana Pérez', 'responsable_mp_cargo' => 'Ingeniera biomédica']);
        $enGarantia = Equipo::factory()->for($this->otroRecinto)->create(['nombre' => 'Máquina de anestesia E', 'en_garantia' => true, 'garantia_anio_vencimiento' => self::ANIO + 2]);
        $this->plan($enGarantia, FrecuenciaAnual::Una);

        $equipos = app(ObtenerInformeCriticosAction::class)
            ->execute(self::ANIO, Periodo::Anual, $this->otroRecinto->id)['equipos']
            ->keyBy('equipo');

        $this->assertTrue($equipos['Máquina de anestesia E']['periodicidad_fabricante']);
        $this->assertFalse($equipos['Ventilador A']['periodicidad_fabricante']);

        $resumen = collect((new InformeCriticosExport(self::ANIO, $this->otroRecinto->id))->sheets()[0]->array())
            ->pluck(1, 0);

        $this->assertSame('Ana Pérez (Ingeniera biomédica)', $resumen['Responsable de MP']);
    }

    public function test_genera_un_xlsx_real_con_las_tres_hojas(): void
    {
        $contenido = Excel::raw(new InformeCriticosExport(self::ANIO, periodo: Periodo::PrimerSemestre), ExcelFormato::XLSX);
        $archivo = tempnam(sys_get_temp_dir(), 'informe').'.xlsx';
        file_put_contents($archivo, $contenido);

        $libro = IOFactory::load($archivo);

        $this->assertSame(['Resumen', 'Equipos críticos', 'Reprogramaciones'], $libro->getSheetNames());
        $reprogramaciones = $libro->getSheetByName('Reprogramaciones');
        $this->assertSame('Causa', $reprogramaciones->getCell('E1')->getValue());
        $this->assertSame('Repuesto sin stock', $reprogramaciones->getCell('E2')->getValue());
        $this->assertSame('Equipo en uso continuo', $reprogramaciones->getCell('E3')->getValue());

        unlink($archivo);
    }

    public function test_reportes_muestra_el_resumen_y_descarga_el_informe_en_tres_hojas(): void
    {
        Excel::fake();
        $this->actuarComo('Encargado de Mantención');

        Livewire::test(Reportes::class)
            ->set('filtros.periodo', Periodo::PrimerSemestre->value)
            ->assertSee('1 de 3 equipos críticos con MP completa en el 1er semestre de 2026')
            ->assertSee('2 reprogramaciones en el período')
            ->callAction('descargarInformeCriticos');

        Excel::assertDownloaded('informe-criticos-2026-s1.xlsx', function (InformeCriticosExport $export): bool {
            $hojas = $export->sheets();

            return array_map(fn (HojaExport $hoja): string => $hoja->title(), $hojas) === ['Resumen', 'Equipos críticos', 'Reprogramaciones']
                && count($hojas[1]->array()) === 3
                && count($hojas[2]->array()) === 2;
        });
    }
}
