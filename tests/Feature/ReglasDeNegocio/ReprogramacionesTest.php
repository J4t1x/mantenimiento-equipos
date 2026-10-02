<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Exceptions\ReprogramacionSinCausaException;
use App\Filament\Resources\EjecucionesMensuales\Pages\EditEjecucionMensual;
use App\Filament\Widgets\CatastroAlertasWidget;
use App\Models\EjecucionMensual;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * RF-66 (RN-09, Res. Ex. 1341/2017 §7.4): una reprogramación exige su causa, y un equipo crítico
 * cuya MP reprogramada no se realizó dentro de 30 días genera una alerta de retiro de uso.
 */
class ReprogramacionesTest extends TestCase
{
    use RefreshDatabase;

    private const CAUSA = 'Equipo en uso continuo en UCI';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 9, 15));
    }

    /**
     * Plan trimestral (mar/jun/sep/dic) del año en curso con el mes indicado reprogramado.
     */
    private function reprogramar(Equipo $equipo, int $mes): EjecucionMensual
    {
        $plan = PlanMantenimiento::factory()->for($equipo)->create(['frecuencia_anual' => FrecuenciaAnual::Cuatro]);
        $ejecucion = $plan->ejecucionesMensuales()->where('mes', $mes)->firstOrFail();
        $ejecucion->update(['estado' => EstadoEjecucion::Reprogramado, 'observaciones' => self::CAUSA]);

        return $ejecucion;
    }

    public function test_reprogramar_sin_causa_se_rechaza(): void
    {
        $plan = PlanMantenimiento::factory()->create(['frecuencia_anual' => FrecuenciaAnual::Cuatro]);

        $this->expectException(ReprogramacionSinCausaException::class);

        $plan->ejecucionesMensuales()->where('mes', 6)->first()->update(['estado' => EstadoEjecucion::Reprogramado, 'observaciones' => '  ']);
    }

    public function test_una_reprogramacion_con_causa_se_puede_realizar_despues(): void
    {
        $ejecucion = $this->reprogramar(Equipo::factory()->create(), 6);

        $ejecucion->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => '2026-07-10']);

        $this->assertSame(EstadoEjecucion::Realizado, $ejecucion->fresh()->estado);
    }

    public function test_el_plazo_es_fin_de_mes_mas_30_dias(): void
    {
        $ejecucion = $this->reprogramar(Equipo::factory()->create(), 6);

        $this->assertSame('2026-07-30', $ejecucion->plazoReprogramacion()->toDateString());
    }

    public function test_solo_alerta_criticos_activos_con_el_plazo_vencido_y_sin_realizar(): void
    {
        $vencida = $this->reprogramar(Equipo::factory()->create(), 6);
        $this->reprogramar(Equipo::factory()->relevante()->create(), 6);
        $this->reprogramar(Equipo::factory()->create(['activo' => false]), 6);
        $this->reprogramar(Equipo::factory()->create(), 9);
        $this->reprogramar(Equipo::factory()->create(), 6)->update(['estado' => EstadoEjecucion::Realizado, 'fecha_real' => '2026-07-20']);

        $this->assertSame([$vencida->id], EjecucionMensual::query()->reprogramacionesVencidas()->pluck('id')->all());
    }

    public function test_el_escritorio_cuenta_los_criticos_a_retirar_y_enlaza_a_la_bitacora(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $this->reprogramar(Equipo::factory()->create(), 3);

        $widget = new CatastroAlertasWidget;
        $stats = (new ReflectionMethod($widget, 'getStats'))->invoke($widget);
        $criticosARetirar = collect($stats)->first(fn ($stat): bool => $stat->getLabel() === 'Críticos a retirar de uso');

        $this->assertSame(1, $criticosARetirar->getValue());
        $this->assertStringContainsString('filters[reprogramacion_vencida][isActive]=1', urldecode($criticosARetirar->getUrl()));
    }

    public function test_el_formulario_exige_la_causa_al_reprogramar(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $plan = PlanMantenimiento::factory()->create(['frecuencia_anual' => FrecuenciaAnual::Cuatro]);
        $ejecucion = $plan->ejecucionesMensuales()->where('mes', 6)->firstOrFail();

        Livewire::test(EditEjecucionMensual::class, ['record' => $ejecucion->getRouteKey()])
            ->fillForm(['estado' => EstadoEjecucion::Reprogramado, 'observaciones' => null])
            ->call('save')
            ->assertHasFormErrors(['observaciones' => 'required']);

        $this->assertSame(EstadoEjecucion::Programado, $ejecucion->fresh()->estado);
    }
}
