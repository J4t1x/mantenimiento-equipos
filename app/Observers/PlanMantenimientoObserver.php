<?php

namespace App\Observers;

use App\Actions\GenerarPlanAnualAction;
use App\Models\PlanMantenimiento;

/**
 * Dispara la generación del plan anual (RN-01) al crear un plan o al cambiar su frecuencia
 * (ADR-03 del SAD): a nivel de modelo, no de la UI, para que se cumpla sin importar si el plan
 * se guarda desde Filament, tinker, un seeder o una futura API.
 */
class PlanMantenimientoObserver
{
    public function __construct(private readonly GenerarPlanAnualAction $generarPlanAnual) {}

    public function created(PlanMantenimiento $plan): void
    {
        $this->generarPlanAnual->execute($plan);
    }

    public function updated(PlanMantenimiento $plan): void
    {
        if ($plan->wasChanged('frecuencia_anual')) {
            $this->generarPlanAnual->execute($plan);
        }
    }
}
