<?php

namespace Tests\Feature\Panel;

use App\Filament\Pages\BitacoraAnual;
use App\Filament\Resources\Convenios\Pages\ListConvenios;
use App\Filament\Resources\Equipos\Pages\ListEquipos;
use App\Filament\Resources\PlanMantenimientos\Pages\CreatePlanMantenimiento;
use App\Filament\Resources\PlanMantenimientos\Pages\EditPlanMantenimiento;
use App\Filament\Widgets\GastoMensualWidget;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SPEC-ME-010: mensajes de validación en español, títulos con mayúscula solo inicial, menú lateral
 * institucional, columnas por defecto de los listados, bitácora anual compacta y gráfico de gasto
 * sin datos.
 */
class AjustesVisualesTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_mensajes_de_validacion_estan_en_espanol(): void
    {
        $this->assertSame('El campo año es obligatorio.', __('validation.required', ['attribute' => 'año']));
        $this->assertSame('El campo nombre no debe tener más de 100 caracteres.', __('validation.max.string', ['attribute' => 'nombre', 'max' => 100]));

        $this->actuarComo('Encargado de Mantención');

        Livewire::test(CreatePlanMantenimiento::class)
            ->call('create')
            ->assertHasFormErrors(['equipo_id' => 'required'])
            ->assertSee('es obligatorio')
            ->assertDontSee('validation.required');
    }

    public function test_titulos_y_migas_con_mayuscula_solo_inicial(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $plan = PlanMantenimiento::factory()->create();

        $this->get(EditPlanMantenimiento::getUrl(['record' => $plan]))
            ->assertOk()
            ->assertSee('Editar plan de mantenimiento')
            ->assertSee('Planes de mantenimiento')
            ->assertDontSee('Plan De Mantenimiento');
    }

    public function test_roles_en_administracion_y_menu_lateral_institucional(): void
    {
        $this->actuarComo('super_admin');

        $this->get('/admin')
            ->assertOk()
            ->assertDontSee('Filament Shield')
            ->assertSee('--menu-fondo: #003C71', false)
            ->assertSee('--menu-franja: #E2001A', false);
    }

    public function test_equipos_muestra_las_columnas_clave_y_busca_en_las_ocultas(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $equipo = Equipo::factory()->create(['marca' => 'Nihon Kohden']);
        $otro = Equipo::factory()->create(['marca' => 'Dräger']);

        Livewire::test(ListEquipos::class)
            ->assertCanRenderTableColumn('n_inventario')
            ->assertCanRenderTableColumn('criticidad')
            ->assertCanRenderTableColumn('planActual.frecuencia_anual')
            ->assertCanNotRenderTableColumn('marca')
            ->assertCanNotRenderTableColumn('recinto.nombre')
            ->searchTable('Nihon')
            ->assertCanSeeTableRecords([$equipo])
            ->assertCanNotSeeTableRecords([$otro]);
    }

    public function test_convenios_oculta_las_columnas_secundarias(): void
    {
        $this->actuarComo('Encargado de Convenios');

        Livewire::test(ListConvenios::class)
            ->assertCanRenderTableColumn('vigencia')
            ->assertCanNotRenderTableColumn('subasignacion_sigfe')
            ->assertCanNotRenderTableColumn('fecha_resolucion');
    }

    public function test_bitacora_anual_compacta(): void
    {
        $this->actuarComo('Encargado de Mantención');
        PlanMantenimiento::factory()->create();

        Livewire::test(BitacoraAnual::class)
            ->assertCanNotRenderTableColumn('equipo.servicioClinico.nombre')
            ->assertDontSee('Realizadas / programadas');
    }

    public function test_el_grafico_de_gasto_sin_datos_lo_indica(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 15));
        $this->actuarComo('Jefatura');

        Livewire::test(GastoMensualWidget::class)->assertSee('Sin gasto registrado en 2026');
    }
}
