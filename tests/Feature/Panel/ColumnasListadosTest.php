<?php

namespace Tests\Feature\Panel;

use App\Enums\FrecuenciaAnual;
use App\Exports\ConveniosFiltradosExport;
use App\Exports\EquiposFiltradosExport;
use App\Filament\Resources\Convenios\Pages\ListConvenios;
use App\Filament\Resources\Equipos\Pages\ListEquipos;
use App\Filament\Resources\ServicioClinicos\Pages\ListServicioClinicos;
use App\Models\Convenio;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use App\Models\Recinto;
use App\Models\ServicioClinico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RF-84 a RF-86 (Módulo 19): equipos asociados por servicio clínico, frecuencia MP del año en el
 * listado de Equipos y vigencia de los convenios como estado.
 */
class ColumnasListadosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 10, 15));
    }

    public function test_cuenta_los_equipos_de_cada_servicio_dentro_del_alcance(): void
    {
        [$propio, $ajeno] = Recinto::factory()->count(2)->create();
        $servicio = ServicioClinico::factory()->create();
        Equipo::factory()->count(2)->create(['servicio_clinico_id' => $servicio->id, 'recinto_id' => $propio->id]);
        Equipo::factory()->create(['servicio_clinico_id' => $servicio->id, 'recinto_id' => $ajeno->id]);

        $this->actuarComo('Encargado de Mantención');
        Livewire::test(ListServicioClinicos::class)->assertTableColumnStateSet('equipos_count', 3, $servicio);

        $acotado = User::factory()->create();
        $acotado->recintos()->attach($propio->id);
        $this->actuarComo('Encargado de Mantención', $acotado);
        Livewire::test(ListServicioClinicos::class)->assertTableColumnStateSet('equipos_count', 2, $servicio);
    }

    public function test_muestra_la_frecuencia_del_plan_del_anio_sin_una_consulta_por_fila(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $conPlan = Equipo::factory()->create();
        PlanMantenimiento::factory()->for($conPlan)->create(['anio' => 2026, 'frecuencia_anual' => FrecuenciaAnual::Dos]);
        $soloAnioAnterior = Equipo::factory()->create();
        PlanMantenimiento::factory()->for($soloAnioAnterior)->create(['anio' => 2025, 'frecuencia_anual' => FrecuenciaAnual::Cuatro]);
        Equipo::factory()->count(5)->create();

        DB::enableQueryLog();
        $listado = Livewire::test(ListEquipos::class);
        $consultasDePlanes = collect(DB::getQueryLog())
            ->filter(fn (array $consulta): bool => str_starts_with($consulta['query'], 'select') && str_contains($consulta['query'], 'from "planes_mantenimiento"'))
            ->count();
        DB::disableQueryLog();

        // Una sola consulta (carga anticipada de `planActual`) para las 7 filas del listado.
        $this->assertSame(1, $consultasDePlanes);

        $listado
            ->assertTableColumnFormattedStateSet('planActual.frecuencia_anual', '2x / año', $conPlan)
            ->assertTableColumnStateNotSet('planActual.frecuencia_anual', FrecuenciaAnual::Cuatro, $soloAnioAnterior)
            ->assertSee('Sin plan');
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function vigencias(): array
    {
        return [
            'vencido' => ['2026-10-14', 'Vencido', 'danger'],
            'vence hoy' => ['2026-10-15', 'Vence hoy', 'warning'],
            'vence mañana' => ['2026-10-16', 'Vence en 1 día', 'warning'],
            'vence en 10 días' => ['2026-10-25', 'Vence en 10 días', 'warning'],
            'límite de 60 días' => ['2026-12-14', 'Vence en 60 días', 'warning'],
            'vigente' => ['2027-05-03', 'Vigente', 'success'],
        ];
    }

    #[DataProvider('vigencias')]
    public function test_la_vigencia_del_convenio_como_estado(string $expiracion, string $etiqueta, string $color): void
    {
        $convenio = Convenio::factory()->create(['fecha_expiracion' => $expiracion]);

        $this->assertSame(['etiqueta' => $etiqueta, 'color' => $color], $convenio->estadoVigencia());
    }

    public function test_el_listado_de_convenios_muestra_la_vigencia(): void
    {
        $this->actuarComo('Encargado de Convenios');
        $porVencer = Convenio::factory()->create(['fecha_expiracion' => '2026-10-25']);
        $vencido = Convenio::factory()->create(['fecha_expiracion' => '2026-01-31']);

        Livewire::test(ListConvenios::class)
            ->assertTableColumnStateSet('vigencia', 'Vence en 10 días', $porVencer)
            ->assertTableColumnStateSet('vigencia', 'Vencido', $vencido);
    }

    public function test_las_exportaciones_incluyen_las_columnas_nuevas(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $equipo = Equipo::factory()->create();
        PlanMantenimiento::factory()->for($equipo)->create(['anio' => 2026, 'frecuencia_anual' => FrecuenciaAnual::Dos]);
        $convenio = Convenio::factory()->create(['fecha_expiracion' => '2026-10-25']);

        $equipos = new EquiposFiltradosExport(Equipo::query());
        $convenios = new ConveniosFiltradosExport(Convenio::query());

        $this->assertContains('Frecuencia MP 2026', $equipos->headings());
        $this->assertContains('2 veces al año', $equipos->map($equipos->query()->first()));
        $this->assertContains('Vigencia', $convenios->headings());
        $this->assertContains('Vence en 10 días', $convenios->map($convenio));
    }
}
