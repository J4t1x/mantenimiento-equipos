<?php

namespace Tests\Feature\Panel;

use App\Filament\Resources\Equipos\EquipoResource;
use App\Models\Equipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Barra superior institucional: nombre del sistema, perfil de la sesión y resultados de la
 * búsqueda global que se distinguen entre equipos con el mismo nombre.
 */
class BarraSuperiorTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_barra_muestra_el_nombre_del_sistema_y_el_perfil_de_la_sesion(): void
    {
        $this->actuarComo('Encargado de Mantención');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('bm-marca', false)
            ->assertSee('Servicio de Salud Aysén · Equipos Médicos')
            ->assertSee('bm-perfil', false)
            ->assertSee('Encargado de Mantención');
    }

    public function test_el_administrador_se_muestra_con_su_nombre_de_perfil(): void
    {
        $this->actuarComo('super_admin');

        $this->get('/admin')
            ->assertOk()
            ->assertSeeInOrder(['bm-perfil', 'Administrador'], false);
    }

    public function test_los_resultados_de_busqueda_de_equipos_con_el_mismo_nombre_se_distinguen(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $primero = Equipo::factory()->create(['nombre' => 'Monitor Desfibrilador', 'n_inventario' => '0084-12']);
        $segundo = Equipo::factory()->create(['nombre' => 'Monitor Desfibrilador', 'n_inventario' => '0087-12']);

        $this->assertSame('0084-12 · Monitor Desfibrilador', EquipoResource::getGlobalSearchResultTitle($primero));
        $this->assertNotSame(
            EquipoResource::getGlobalSearchResultTitle($primero),
            EquipoResource::getGlobalSearchResultTitle($segundo),
        );

        $detalles = EquipoResource::getGlobalSearchResultDetails($primero->fresh());

        $this->assertSame($primero->servicioClinico->nombre, $detalles['Servicio']);
        $this->assertSame($primero->recinto->nombre, $detalles['Recinto']);
    }

    public function test_la_busqueda_global_encuentra_el_equipo_por_inventario(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $equipo = Equipo::factory()->create(['nombre' => 'Incubadora Intensivo', 'n_inventario' => '0286-12']);

        $resultados = EquipoResource::getGlobalSearchResults('0286-12');

        $this->assertCount(1, $resultados);
        $this->assertSame('0286-12 · Incubadora Intensivo', $resultados->first()->title);
        $this->assertSame(EquipoResource::getUrl('view', ['record' => $equipo]), $resultados->first()->url);
    }
}
