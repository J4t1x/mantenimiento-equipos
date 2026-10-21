<?php

namespace App\Actions;

use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Models\EjecucionMensual;
use App\Models\PlanMantenimiento;

/**
 * Genera o regenera las 12 marcas mensuales de un plan de mantenimiento preventivo, distribuyendo
 * la cantidad exacta de meses "Programado" según la frecuencia anual (RN-01, RF-13/RF-14 del SRS).
 *
 * Idempotente (RF-15, ADR-03 del SAD): al regenerar (p. ej. porque la frecuencia cambió a mitad
 * de año) no toca los meses que ya quedaron "realizado" o "reprogramado".
 *
 * Algoritmo de distribución ({@see FrecuenciaAnual::mesesProgramados()}): meses uniformemente
 * espaciados terminando en diciembre. El propio BRIEF §5 deja este algoritmo "a definir en Etapa 2
 * con el requirente"; se adopta el reparto uniforme por ser el criterio más simple y ser
 * consistente con su propio ejemplo (frecuencia 4 → trimestral: marzo/junio/septiembre/diciembre).
 */
class GenerarPlanAnualAction
{
    public function execute(PlanMantenimiento $plan): void
    {
        $mesesProgramados = FrecuenciaAnual::desde($plan->frecuencia_anual)?->mesesProgramados() ?? [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $ejecucion = EjecucionMensual::firstOrNew([
                'plan_mantenimiento_id' => $plan->id,
                'mes' => $mes,
            ]);

            // No se toca un mes que ya fue ejecutado o reprogramado (RF-15).
            if ($ejecucion->exists && in_array($ejecucion->estado, [
                EstadoEjecucion::Realizado,
                EstadoEjecucion::Reprogramado,
            ], true)) {
                continue;
            }

            $ejecucion->estado = in_array($mes, $mesesProgramados, true)
                ? EstadoEjecucion::Programado
                : EstadoEjecucion::SinProgramar;

            $ejecucion->save();
        }
    }
}
