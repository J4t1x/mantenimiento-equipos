<?php

namespace App\Console\Commands;

use App\Enums\EstadoEjecucion;
use App\Models\PlanMantenimiento;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * SPEC-ME-016 (RN-01): completa los planes que tienen menos de 12 registros mensuales. El
 * importador original solo creaba los meses marcados en la planilla, así que esos planes no
 * mostraban los meses "Sin programar" en la grilla.
 *
 * Por defecto solo muestra lo que haría. Con `--aplicar` inserta los meses faltantes como
 * "Sin programar", sin modificar los registros existentes (no cambia ninguna marca). Es
 * idempotente: una segunda ejecución no encuentra nada que completar. Al final informa los
 * planes cuyos meses programados no coinciden con la frecuencia, que se revisan a mano.
 */
#[Signature('app:completar-meses-planes {--aplicar : Insertar los meses faltantes como Sin programar}')]
#[Description('Completa los planes de mantenimiento hasta sus 12 meses (RN-01) e informa los planes con meses inconsistentes.')]
class CompletarMesesPlanes extends Command
{
    public function handle(): int
    {
        $aplicar = (bool) $this->option('aplicar');
        $planesIncompletos = 0;
        $mesesFaltantes = 0;

        PlanMantenimiento::query()
            ->withoutGlobalScopes()
            ->has('ejecucionesMensuales', '<', 12)
            ->with('ejecucionesMensuales:id,plan_mantenimiento_id,mes')
            ->orderBy('id')
            ->chunkById(200, function ($planes) use ($aplicar, &$planesIncompletos, &$mesesFaltantes): void {
                $filas = [];

                foreach ($planes as $plan) {
                    $faltantes = array_diff(range(1, 12), $plan->ejecucionesMensuales->pluck('mes')->all());
                    $planesIncompletos++;
                    $mesesFaltantes += count($faltantes);

                    foreach ($faltantes as $mes) {
                        $filas[] = [
                            'plan_mantenimiento_id' => $plan->id,
                            'mes' => $mes,
                            'estado' => EstadoEjecucion::SinProgramar->value,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if ($aplicar && $filas !== []) {
                    DB::table('ejecuciones_mensuales')->insertOrIgnore($filas);
                }
            });

        $this->info($aplicar
            ? "Planes completados: {$planesIncompletos} ({$mesesFaltantes} meses agregados como Sin programar)."
            : "Planes por completar: {$planesIncompletos} ({$mesesFaltantes} meses faltantes). Sin cambios; usa --aplicar.");

        $this->informarInconsistentes();

        return self::SUCCESS;
    }

    private function informarInconsistentes(): void
    {
        $inconsistentes = PlanMantenimiento::query()
            ->withoutGlobalScopes()
            ->conMesesInconsistentes()
            ->with(['equipo' => fn ($query) => $query->withoutGlobalScopes()])
            ->withCount(['ejecucionesMensuales as meses_programados' => fn ($query) => $query->where('estado', '!=', EstadoEjecucion::SinProgramar->value)])
            ->orderBy('id')
            ->get();

        if ($inconsistentes->isEmpty()) {
            $this->line('No hay planes con meses programados distintos de la frecuencia.');

            return;
        }

        $this->warn("Planes con meses programados distintos de la frecuencia: {$inconsistentes->count()} (revisar a mano, el comando no los modifica).");
        $this->table(
            ['Plan', 'Año', 'Equipo', 'N° inventario', 'Frecuencia', 'Meses programados'],
            $inconsistentes->map(fn (PlanMantenimiento $plan): array => [
                $plan->id,
                $plan->anio,
                $plan->equipo?->nombre,
                $plan->equipo?->n_inventario,
                $plan->frecuencia_anual?->getLabel(),
                $plan->meses_programados,
            ])->all(),
        );
    }
}
