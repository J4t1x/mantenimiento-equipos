<?php

namespace Tests\Feature\ReglasDeNegocio;

use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RN-01 (RF-13 a RF-15): al crear un plan se generan sus 12 meses, con la cantidad exacta de meses
 * programados según la frecuencia anual; al cambiar la frecuencia se regenera sin tocar lo ya
 * ejecutado o reprogramado. Se usa un equipo relevante porque la frecuencia 1 no se admite en
 * equipos críticos (RF-65).
 */
class GeneracionPlanAnualTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{FrecuenciaAnual, array<int, int>}>
     */
    public static function frecuencias(): array
    {
        return [
            'anual' => [FrecuenciaAnual::Una, [12]],
            'semestral' => [FrecuenciaAnual::Dos, [6, 12]],
            'cuatrimestral' => [FrecuenciaAnual::Tres, [4, 8, 12]],
            'trimestral' => [FrecuenciaAnual::Cuatro, [3, 6, 9, 12]],
            'bimestral' => [FrecuenciaAnual::Seis, [2, 4, 6, 8, 10, 12]],
            'mensual' => [FrecuenciaAnual::Doce, range(1, 12)],
        ];
    }

    /**
     * @param  array<int, int>  $mesesEsperados
     */
    #[DataProvider('frecuencias')]
    public function test_genera_los_12_meses_con_los_programados_de_la_frecuencia(FrecuenciaAnual $frecuencia, array $mesesEsperados): void
    {
        $plan = PlanMantenimiento::factory()
            ->for(Equipo::factory()->relevante())
            ->create(['frecuencia_anual' => $frecuencia]);

        $ejecuciones = $plan->ejecucionesMensuales()->orderBy('mes')->get();

        $this->assertCount(12, $ejecuciones);
        $this->assertSame(
            $mesesEsperados,
            $ejecuciones->where('estado', EstadoEjecucion::Programado)->pluck('mes')->values()->all(),
        );
    }

    public function test_al_cambiar_la_frecuencia_conserva_los_meses_ya_realizados(): void
    {
        $plan = PlanMantenimiento::factory()
            ->for(Equipo::factory()->relevante())
            ->create(['frecuencia_anual' => FrecuenciaAnual::Cuatro]);
        $plan->ejecucionesMensuales()->where('mes', 3)->first()->update([
            'estado' => EstadoEjecucion::Realizado,
            'fecha_real' => now(),
        ]);

        $plan->update(['frecuencia_anual' => FrecuenciaAnual::Una]);

        $estados = $plan->ejecucionesMensuales()->pluck('estado', 'mes');

        $this->assertSame(EstadoEjecucion::Realizado, $estados[3]);
        $this->assertSame(EstadoEjecucion::Programado, $estados[12]);
        $this->assertSame(EstadoEjecucion::SinProgramar, $estados[6]);
        $this->assertSame(12, $plan->ejecucionesMensuales()->count());
    }
}
