<?php

namespace App\Actions;

use App\Models\Convenio;
use Filament\Notifications\Notification;

/**
 * RN-05 del BRIEF (RF-29): "el sistema debe alertar (sin bloquear) cuando la suma de los montos
 * mensuales ejecutados de un convenio supere su monto anual". El registro de la ejecución mensual
 * nunca se impide (RF-29 es explícito en "sin bloquear"); esta acción solo emite la alerta
 * después de guardar.
 *
 * RF-47 (Módulo 12): además del toast (`send()`, se pierde si el usuario no está mirando la
 * pantalla exacta en el momento en que se dispara), la alerta se persiste a la campana de
 * notificaciones del usuario que la disparó (`sendToDatabase()`).
 */
class NotificarSobregiroConvenioAction
{
    public function execute(Convenio $convenio, int $anio): void
    {
        $ejecucion = app(CalcularEjecucionConvenioAction::class)->paraConvenio($convenio, $anio);

        if (! $ejecucion['sobregirado']) {
            return;
        }

        $notification = Notification::make()
            ->warning()
            ->title('Convenio sobregirado')
            ->body(sprintf(
                'El convenio "%s" registra %s%% de ejecución en %d, superando su monto anual.',
                $convenio->nombre,
                $ejecucion['porcentaje'],
                $anio,
            ));

        $notification->send();

        if ($usuario = auth()->user()) {
            $notification->sendToDatabase($usuario);
        }
    }
}
