<?php

namespace Tests\Feature\Panel;

use App\Filament\Resources\Recintos\Pages\EditRecinto;
use App\Filament\Resources\Recintos\Pages\ListRecintos;
use App\Filament\Resources\Recintos\Pages\ViewRecinto;
use App\Filament\Resources\Recintos\RelationManagers\PresupuestosMantenimientoRelationManager;
use App\Filament\Resources\Recintos\RelationManagers\ProgramasAnualesMantenimientoRelationManager;
use App\Filament\Widgets\CatastroAlertasWidget;
use App\Models\Recinto;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * SPEC-ME-014 (hallazgo H-02 de la prueba funcional del 03-11-2026): el encargado de un
 * establecimiento, con el recinto asignado, abre la ficha de su recinto y registra en ella el
 * responsable de MP, el programa anual y el gasto programado, sin administrar el catálogo. La
 * Jefatura ve la ficha en solo lectura.
 */
class FichaRecintoTest extends TestCase
{
    use RefreshDatabase;

    private Recinto $propio;

    private Recinto $ajeno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 10, 15));
        $this->propio = Recinto::factory()->create(['nombre' => 'Hospital de Puerto Aysén']);
        $this->ajeno = Recinto::factory()->create(['nombre' => 'Hospital Regional Coyhaique']);
    }

    private function encargadoDelEstablecimiento(): User
    {
        $usuario = User::factory()->create();
        $usuario->recintos()->attach($this->propio->id);

        return $this->actuarComo('Encargado de Mantención', $usuario);
    }

    private function relationManager(string $clase, Recinto $recinto): mixed
    {
        return Livewire::test($clase, ['ownerRecord' => $recinto, 'pageClass' => ViewRecinto::class]);
    }

    public function test_el_encargado_abre_la_ficha_de_su_recinto_y_no_la_de_otro(): void
    {
        $this->encargadoDelEstablecimiento();

        $this->get(ViewRecinto::getUrl(['record' => $this->propio]))
            ->assertOk()
            ->assertSee('Responsable de mantenimiento preventivo')
            ->assertSee('Programa anual de MP')
            ->assertSee('Gasto programado anual');

        $this->get(ViewRecinto::getUrl(['record' => $this->ajeno]))->assertNotFound();
    }

    public function test_el_encargado_registra_el_programa_anual_y_el_gasto_programado(): void
    {
        $this->encargadoDelEstablecimiento();

        $this->relationManager(ProgramasAnualesMantenimientoRelationManager::class, $this->propio)
            ->callAction(TestAction::make('create')->table(), [
                'anio' => 2026,
                'fecha_definicion' => '2026-03-20',
                'fecha_validacion' => '2026-03-28',
                'documento_validacion' => 'Res. Ex. N° 77',
                'validado_por_nombre' => 'Director del establecimiento',
            ])
            ->assertHasNoFormErrors();

        $this->relationManager(PresupuestosMantenimientoRelationManager::class, $this->propio)
            ->callAction(TestAction::make('create')->table(), [
                'anio' => 2026,
                'gasto_programado_mp' => 120_000_000,
                'gasto_programado_mc' => 80_000_000,
            ])
            ->assertHasNoFormErrors();

        $this->assertSame('Res. Ex. N° 77', $this->propio->programasAnualesMantenimiento()->sole()->documento_validacion);
        $this->assertSame(120_000_000.0, (float) $this->propio->presupuestosMantenimiento()->sole()->gasto_programado_mp);
    }

    public function test_el_encargado_actualiza_el_responsable_de_mp_desde_la_ficha(): void
    {
        $this->encargadoDelEstablecimiento();

        Livewire::test(ViewRecinto::class, ['record' => $this->propio->getRouteKey()])
            ->assertActionHidden('edit')
            ->callAction('editarResponsableMp', [
                'responsable_mp_nombre' => 'María Soto',
                'responsable_mp_cargo' => 'Ingeniera biomédica',
                'responsable_mp_documento' => 'Res. Ex. N° 1234',
                'responsable_mp_fecha_designacion' => '2026-01-15',
            ])
            ->assertHasNoFormErrors();

        $this->assertSame('María Soto', $this->propio->fresh()->responsable_mp_nombre);
    }

    public function test_el_encargado_no_administra_el_catalogo_de_recintos(): void
    {
        $this->encargadoDelEstablecimiento();

        Livewire::test(ListRecintos::class)->assertActionHidden('create');
        $this->get(EditRecinto::getUrl(['record' => $this->propio]))->assertForbidden();
    }

    public function test_la_jefatura_ve_la_ficha_en_solo_lectura(): void
    {
        $this->actuarComo('Jefatura');

        $this->get(ViewRecinto::getUrl(['record' => $this->propio]))->assertOk()->assertSee('Programa anual de MP');

        Livewire::test(ViewRecinto::class, ['record' => $this->propio->getRouteKey()])
            ->assertActionHidden('editarResponsableMp')
            ->assertActionHidden('edit');

        $this->relationManager(ProgramasAnualesMantenimientoRelationManager::class, $this->propio)
            ->assertActionHidden(TestAction::make('create')->table());
        $this->relationManager(PresupuestosMantenimientoRelationManager::class, $this->propio)
            ->assertActionHidden(TestAction::make('create')->table());
    }

    public function test_la_alerta_de_programa_sin_validar_lleva_a_la_ficha_del_recinto_del_encargado(): void
    {
        $alerta = function (): ?Stat {
            $widget = new CatastroAlertasWidget;
            $stats = (new ReflectionMethod($widget, 'getStats'))->invoke($widget);

            return collect($stats)->first(fn (Stat $stat): bool => $stat->getLabel() === 'Programa 2026 sin validar');
        };

        $this->encargadoDelEstablecimiento();
        $this->assertStringEndsWith('/recintos/'.$this->propio->id, $alerta()->getUrl());

        $this->actuarComo('Encargado de Mantención');
        $this->assertStringContainsString('filters', urldecode($alerta()->getUrl()));
    }
}
