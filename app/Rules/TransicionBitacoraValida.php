<?php

namespace App\Rules;

use App\Enums\EstadoEjecucion;
use App\Models\EjecucionMensual;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Versión "amigable" de RN-02 para el formulario de Filament: mismo criterio que la guarda de
 * modelo en {@see EjecucionMensual}, pero con un mensaje de validación en el campo en vez de una
 * excepción genérica.
 */
class TransicionBitacoraValida implements ValidationRule
{
    public function __construct(private readonly ?EjecucionMensual $record = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $estadoAnterior = $this->record?->estado;
        $estadoNuevo = EstadoEjecucion::tryFrom((string) $value);

        if ($estadoNuevo === null) {
            return;
        }

        if (! EstadoEjecucion::esTransicionValida($estadoAnterior, $estadoNuevo)) {
            $fail('No se puede marcar como "Realizado" o "Reprogramado" un mes sin una marca '
                .'"Programado" previa (RN-02).');
        }
    }
}
