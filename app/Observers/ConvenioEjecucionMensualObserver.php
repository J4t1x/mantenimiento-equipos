<?php

namespace App\Observers;

use App\Actions\NotificarSobregiroConvenioAction;
use App\Models\ConvenioEjecucionMensual;

/**
 * RF-49 del SRS (Módulo 12): la alerta de sobregiro (RN-05) solo se disparaba desde las páginas
 * del recurso standalone `ConvenioEjecucionMensualResource` (`afterCreate`/`afterSave`), no desde
 * el `RelationManager` de `Convenios` — la misma tabla tenía dos puntos de entrada con
 * comportamiento distinto. Se mueve al modelo (mismo criterio que `PlanMantenimientoObserver`)
 * para que se cumpla sin importar desde qué UI (o una futura API) se guarde el registro.
 */
class ConvenioEjecucionMensualObserver
{
    public function __construct(private readonly NotificarSobregiroConvenioAction $notificarSobregiro) {}

    public function created(ConvenioEjecucionMensual $ejecucion): void
    {
        $this->notificarSobregiro->execute($ejecucion->convenio, $ejecucion->anio);
    }

    public function updated(ConvenioEjecucionMensual $ejecucion): void
    {
        $this->notificarSobregiro->execute($ejecucion->convenio, $ejecucion->anio);
    }
}
