<?php

namespace App\Filament\Support;

use App\Exceptions\ExcepcionDeDominio;
use Closure;
use Filament\Notifications\Notification;
use Throwable;

use function Livewire\on;

/**
 * Corrección de RNF-04 (SPEC-ME-002): las reglas de negocio se validan en el formulario y, como
 * defensa en profundidad, en el modelo con excepciones de dominio. Si una de esas excepciones
 * llegaba al panel (porque la validación del formulario no la atajó), el usuario veía un error 500.
 *
 * Se engancha al evento `exception` de Livewire, que se dispara ante cualquier excepción en una
 * acción o un render de un componente. Si es una {@see ExcepcionDeDominio}, se muestra como
 * notificación y se detiene su propagación: la acción no se completa (la transacción de Filament ya
 * se revirtió) y el formulario o el modal quedan abiertos para corregir. Cualquier otra excepción se
 * propaga igual que antes.
 */
class NotificarExcepcionesDeDominio
{
    public const TITULO = 'No se pudo guardar';

    public static function registrar(): void
    {
        on('exception', function (mixed $componente, Throwable $excepcion, Closure $detenerPropagacion): void {
            if (! $excepcion instanceof ExcepcionDeDominio) {
                return;
            }

            Notification::make()
                ->danger()
                ->title(self::TITULO)
                ->body($excepcion->getMessage())
                ->persistent()
                ->send();

            $detenerPropagacion();
        });
    }
}
