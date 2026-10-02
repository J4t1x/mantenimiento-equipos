<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Enums\Criticidad;
use App\Enums\FrecuenciaAnual;
use App\Enums\TipoMantenimiento;
use App\Exceptions\FrecuenciaInsuficienteException;
use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Filament\Resources\PlanMantenimientos\Pages\CreatePlanMantenimiento;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * RF-65 (RN-08, Res. Ex. 1341/2017 del MINSAL): un equipo crítico bajo plan de MP necesita una
 * frecuencia anual de al menos 2. RF-69 (§7.4.i): salvo con garantía vigente en el año del plan,
 * que admite la periodicidad del fabricante. Se valida en el modelo (RNF-04) y en los formularios.
 */
class FrecuenciaMinimaCriticosTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_equipo_critico_admite_frecuencia_2_y_uno_relevante_frecuencia_1(): void
    {
        PlanMantenimiento::factory()->create(['frecuencia_anual' => FrecuenciaAnual::Dos]);
        PlanMantenimiento::factory()->for(Equipo::factory()->relevante())->create(['frecuencia_anual' => FrecuenciaAnual::Una]);

        $this->assertSame(2, PlanMantenimiento::count());
    }

    public function test_rechaza_crear_un_plan_de_frecuencia_1_para_un_equipo_critico(): void
    {
        $this->expectException(FrecuenciaInsuficienteException::class);

        PlanMantenimiento::factory()->create(['frecuencia_anual' => FrecuenciaAnual::Una]);
    }

    public function test_rechaza_bajar_a_frecuencia_1_el_plan_de_un_equipo_critico(): void
    {
        $plan = PlanMantenimiento::factory()->create(['frecuencia_anual' => FrecuenciaAnual::Cuatro]);

        $this->expectException(FrecuenciaInsuficienteException::class);

        $plan->update(['frecuencia_anual' => FrecuenciaAnual::Una]);
    }

    public function test_rechaza_pasar_a_critico_un_equipo_con_plan_vigente_de_frecuencia_1(): void
    {
        $equipo = Equipo::factory()->relevante()->create();
        PlanMantenimiento::factory()->for($equipo)->create(['frecuencia_anual' => FrecuenciaAnual::Una]);

        $this->expectException(FrecuenciaInsuficienteException::class);

        $equipo->update(['criticidad' => Criticidad::Critico]);
    }

    public function test_el_formulario_de_plan_muestra_el_error_en_la_frecuencia(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $equipo = Equipo::factory()->create();

        Livewire::test(CreatePlanMantenimiento::class)
            ->fillForm([
                'equipo_id' => $equipo->id,
                'anio' => now()->year,
                'frecuencia_anual' => FrecuenciaAnual::Una,
                'tipo_mantenimiento' => TipoMantenimiento::Externo,
            ])
            ->call('create')
            ->assertHasFormErrors(['frecuencia_anual']);

        $this->assertSame(0, PlanMantenimiento::count());
    }

    public function test_el_formulario_de_equipo_muestra_el_error_en_la_criticidad(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $equipo = Equipo::factory()->relevante()->create();
        PlanMantenimiento::factory()->for($equipo)->create(['frecuencia_anual' => FrecuenciaAnual::Una]);

        Livewire::test(EditEquipo::class, ['record' => $equipo->getRouteKey()])
            ->fillForm(['criticidad' => Criticidad::Critico])
            ->call('save')
            ->assertHasFormErrors(['criticidad']);

        $this->assertSame(Criticidad::Relevante, $equipo->fresh()->criticidad);
    }

    public function test_un_critico_con_garantia_vigente_admite_la_periodicidad_del_fabricante(): void
    {
        $conVencimiento = Equipo::factory()->create(['en_garantia' => true, 'garantia_anio_vencimiento' => now()->year + 1]);
        $sinVencimiento = Equipo::factory()->create(['en_garantia' => true, 'garantia_anio_vencimiento' => null]);

        PlanMantenimiento::factory()->for($conVencimiento)->create(['frecuencia_anual' => FrecuenciaAnual::Una]);
        PlanMantenimiento::factory()->for($sinVencimiento)->create(['frecuencia_anual' => FrecuenciaAnual::Una]);

        $this->assertSame(2, PlanMantenimiento::count());
    }

    public function test_la_garantia_vencida_en_el_anio_del_plan_no_exime_del_minimo(): void
    {
        $equipo = Equipo::factory()->create(['en_garantia' => true, 'garantia_anio_vencimiento' => now()->year]);

        PlanMantenimiento::factory()->for($equipo)->create(['anio' => now()->year, 'frecuencia_anual' => FrecuenciaAnual::Una]);

        $this->expectException(FrecuenciaInsuficienteException::class);

        PlanMantenimiento::factory()->for($equipo)->create(['anio' => now()->year + 1, 'frecuencia_anual' => FrecuenciaAnual::Una]);
    }

    public function test_rechaza_quitar_la_garantia_a_un_critico_con_plan_vigente_de_frecuencia_1(): void
    {
        $equipo = Equipo::factory()->create(['en_garantia' => true, 'garantia_anio_vencimiento' => now()->year + 2]);
        PlanMantenimiento::factory()->for($equipo)->create(['frecuencia_anual' => FrecuenciaAnual::Una]);

        $this->expectException(FrecuenciaInsuficienteException::class);

        $equipo->update(['en_garantia' => false]);
    }

    public function test_el_formulario_de_plan_acepta_frecuencia_1_en_un_critico_en_garantia(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $equipo = Equipo::factory()->create(['en_garantia' => true, 'garantia_anio_vencimiento' => now()->year + 1]);

        Livewire::test(CreatePlanMantenimiento::class)
            ->fillForm([
                'equipo_id' => $equipo->id,
                'anio' => now()->year,
                'frecuencia_anual' => FrecuenciaAnual::Una,
                'tipo_mantenimiento' => TipoMantenimiento::Externo,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, PlanMantenimiento::count());
    }

    public function test_el_formulario_de_equipo_no_deja_quitar_la_garantia_si_el_plan_la_necesita(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $equipo = Equipo::factory()->create(['en_garantia' => true, 'garantia_anio_vencimiento' => now()->year + 1]);
        PlanMantenimiento::factory()->for($equipo)->create(['frecuencia_anual' => FrecuenciaAnual::Una]);

        Livewire::test(EditEquipo::class, ['record' => $equipo->getRouteKey()])
            ->fillForm(['en_garantia' => false])
            ->call('save')
            ->assertHasFormErrors(['criticidad']);

        $this->assertTrue($equipo->fresh()->en_garantia);
    }
}
