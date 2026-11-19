<?php

namespace Tests\Feature\Panel;

use App\Enums\Criticidad;
use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Filament\Resources\PlanMantenimientos\Pages\CalendarioPlanes;
use App\Filament\Resources\PlanMantenimientos\PlanMantenimientoResource;
use App\Filament\Widgets\CalendarioMantencionesWidget;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use App\Models\Recinto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SPEC-ME-023: calendario de mantenciones (vista anual y mensual) junto al listado de planes.
 */
class CalendarioPlanesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 10, 15));
    }

    /**
     * Plan con frecuencia 4 (marzo, junio, septiembre y diciembre programados por RN-01).
     *
     * @param  array<string, mixed>  $equipo
     */
    private function plan(array $equipo = [], ?string $fechaRealMarzo = null): PlanMantenimiento
    {
        $plan = PlanMantenimiento::factory()
            ->for(Equipo::factory()->create($equipo))
            ->create(['anio' => 2026, 'frecuencia_anual' => FrecuenciaAnual::Cuatro]);

        if ($fechaRealMarzo !== null) {
            $plan->ejecucionesMensuales()->where('mes', 3)->first()->update([
                'estado' => EstadoEjecucion::Realizado,
                'fecha_real' => $fechaRealMarzo,
            ]);
        }

        return $plan;
    }

    public function test_el_listado_enlaza_al_calendario_y_el_calendario_al_listado(): void
    {
        $this->actuarComo('Encargado de Mantención');

        $this->get(PlanMantenimientoResource::getUrl('index'))
            ->assertOk()
            ->assertSee(PlanMantenimientoResource::getUrl('calendario'));

        $this->get(PlanMantenimientoResource::getUrl('calendario'))
            ->assertOk()
            ->assertSee('Calendario de mantenciones')
            ->assertSee(PlanMantenimientoResource::getUrl('index'))
            ->assertSee('Crear plan de mantenimiento');
    }

    public function test_los_perfiles_de_solo_lectura_ven_el_calendario_sin_crear_planes(): void
    {
        $this->actuarComo('Jefatura');

        $this->get(PlanMantenimientoResource::getUrl('calendario'))
            ->assertOk()
            ->assertDontSee('Crear plan de mantenimiento');
    }

    public function test_la_vista_anual_separa_realizadas_programadas_y_atrasadas(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $this->plan(fechaRealMarzo: '2026-03-10');
        $this->plan();

        $resumen = Livewire::test(CalendarioPlanes::class)->instance()->resumenAnual();

        $this->assertEquals(['realizadas' => 1, 'atrasadas' => 1, 'programadas' => 0, 'total' => 2], array_intersect_key($resumen[3], array_flip(['realizadas', 'atrasadas', 'programadas', 'total'])));
        $this->assertSame(2, $resumen[9]['atrasadas']);
        $this->assertSame(2, $resumen[12]['programadas']);
        $this->assertSame(0, $resumen[12]['atrasadas']);
        $this->assertSame(0, $resumen[1]['total']);
        $this->assertTrue($resumen[10]['esActual']);
        $this->assertSame(50, $resumen[3]['avance']);
    }

    public function test_la_vista_del_mes_ubica_las_realizadas_en_su_fecha_y_lista_las_pendientes(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $realizado = $this->plan(['n_inventario' => 'R-001'], fechaRealMarzo: '2026-03-10');
        $this->plan(['n_inventario' => 'P-002']);

        $pagina = Livewire::test(CalendarioPlanes::class)
            ->call('verMes', 3)
            ->assertSet('mes', 3)
            ->assertSee('Marzo 2026')
            ->assertSee('R-001')
            ->assertSee('P-002')
            ->instance();

        $dia10 = collect($pagina->semanasDelMes())->flatten(1)->first(fn (array $dia): bool => $dia['fecha']->toDateString() === '2026-03-10');
        $this->assertSame(['R-001'], $dia10['realizadas']->pluck('inventario')->all());

        $detalle = $pagina->detalleDelMes();
        $this->assertSame(['P-002'], $detalle['pendientes']->pluck('inventario')->all());
        $this->assertSame('atrasada', $detalle['pendientes']->first()['tipo']);
        $this->assertSame(PlanMantenimientoResource::getUrl('view', ['record' => $realizado]), $dia10['realizadas']->first()['url']);
    }

    public function test_las_realizadas_sin_fecha_real_se_listan_aparte(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $plan = $this->plan(['n_inventario' => 'S-003']);
        $plan->ejecucionesMensuales()->where('mes', 3)->first()->updateQuietly(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => null]);

        $detalle = Livewire::test(CalendarioPlanes::class)->call('verMes', 3)->instance()->detalleDelMes();

        $this->assertSame(['S-003'], $detalle['sinFecha']->pluck('inventario')->all());
        $this->assertCount(0, $detalle['pendientes']);
    }

    public function test_la_navegacion_entre_meses_cruza_el_anio(): void
    {
        $this->actuarComo('Encargado de Mantención');

        Livewire::test(CalendarioPlanes::class)
            ->call('verMes', 12)
            ->call('mesSiguiente')
            ->assertSet('anio', 2027)
            ->assertSet('mes', 1)
            ->call('mesAnterior')
            ->assertSet('anio', 2026)
            ->assertSet('mes', 12)
            ->call('verAnio')
            ->assertSet('mes', null);
    }

    public function test_los_filtros_acotan_por_criticidad(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $this->plan(['criticidad' => Criticidad::Critico]);
        $this->plan(['criticidad' => Criticidad::Relevante]);

        $resumen = Livewire::test(CalendarioPlanes::class)
            ->set('criticidad', Criticidad::Critico->value)
            ->instance()
            ->resumenAnual();

        $this->assertSame(1, $resumen[12]['total']);
    }

    public function test_respeta_el_alcance_por_recinto(): void
    {
        [$propio, $ajeno] = Recinto::factory()->count(2)->create();
        $this->plan(['recinto_id' => $propio->id, 'n_inventario' => 'PROPIO-1']);
        $this->plan(['recinto_id' => $ajeno->id, 'n_inventario' => 'AJENO-1']);
        $usuario = User::factory()->create();
        $usuario->recintos()->attach($propio->id);
        $this->actuarComo('Encargado de Mantención', $usuario);

        $pagina = Livewire::test(CalendarioPlanes::class);
        $this->assertSame(1, $pagina->instance()->resumenAnual()[12]['total']);

        $pagina->call('verMes', 12)->assertSee('PROPIO-1')->assertDontSee('AJENO-1');
    }

    public function test_el_escritorio_muestra_el_calendario_en_su_pestana_segun_el_perfil(): void
    {
        $this->actuarComo('Jefatura');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('pestanas-escritorio', false)
            ->assertSeeInOrder(['Requiere atención', 'Calendario', 'Mantenimiento preventivo']);

        $this->actuarComo('Encargado de Convenios');

        $this->assertFalse(CalendarioMantencionesWidget::canView());
    }

    public function test_el_widget_usa_el_anio_y_los_meses_del_periodo_del_escritorio(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $this->plan();

        $widget = Livewire::test(CalendarioMantencionesWidget::class, ['anio' => 2026, 'periodo' => 's2'])->instance();

        $this->assertSame([7, 8, 9, 10, 11, 12], array_keys($widget->resumenAnual()));
        $this->assertSame(1, $widget->resumenAnual()[12]['total']);
        $this->assertSame(PlanMantenimientoResource::getUrl('calendario', ['anio' => 2026]), $widget->urlCalendarioCompleto());
    }

    public function test_en_el_widget_la_navegacion_entre_meses_no_sale_del_anio_del_escritorio(): void
    {
        $this->actuarComo('Encargado de Mantención');

        Livewire::test(CalendarioMantencionesWidget::class, ['anio' => 2026])
            ->call('verMes', 12)
            ->call('mesSiguiente')
            ->assertSet('mes', 12)
            ->assertSet('anio', 2026)
            ->call('verMes', 1)
            ->call('mesAnterior')
            ->assertSet('mes', 1);
    }
}
