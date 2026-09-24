<?php

namespace Tests\Feature\Panel;

use App\Filament\Resources\Recintos\Pages\EditRecinto;
use App\Filament\Resources\Recintos\Pages\ListRecintos;
use App\Models\Equipo;
use App\Models\Recinto;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * RF-04: un valor de catálogo referenciado por un equipo no se puede eliminar (solo desactivar);
 * uno sin referencias sí. Se prueba en Recintos; los 4 catálogos usan la misma
 * `AccionesEliminarProtegidas`.
 */
class CatalogosTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_elimina_desde_la_ficha_un_recinto_con_equipos(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $recinto = Equipo::factory()->create()->recinto;

        Livewire::test(EditRecinto::class, ['record' => $recinto->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertModelExists($recinto);
    }

    public function test_la_eliminacion_en_lote_no_borra_si_alguno_tiene_equipos(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $conEquipos = Equipo::factory()->create()->recinto;
        $sinEquipos = Recinto::factory()->create();

        Livewire::test(ListRecintos::class)
            ->selectTableRecords([$conEquipos, $sinEquipos])
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertModelExists($conEquipos);
        $this->assertModelExists($sinEquipos);
    }

    public function test_elimina_un_recinto_sin_equipos(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $recinto = Recinto::factory()->create();

        Livewire::test(EditRecinto::class, ['record' => $recinto->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertModelMissing($recinto);
    }
}
