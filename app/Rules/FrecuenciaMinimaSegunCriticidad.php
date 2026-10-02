<?php

namespace App\Rules;

use App\Enums\FrecuenciaAnual;
use App\Exceptions\FrecuenciaInsuficienteException;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Versión "amigable" de RF-65/RF-69 (RN-08) para los formularios de Filament: mismo criterio que la
 * guarda de modelo en {@see PlanMantenimiento} ({@see Equipo::admiteFrecuencia()}), pero con un
 * mensaje de validación en el campo en vez de una excepción genérica.
 */
class FrecuenciaMinimaSegunCriticidad implements ValidationRule
{
    public function __construct(
        private readonly ?Equipo $equipo,
        private readonly ?int $anio,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $frecuencia = $value instanceof FrecuenciaAnual ? $value : FrecuenciaAnual::tryFrom((string) $value);

        if ($this->equipo === null || $frecuencia === null) {
            return;
        }

        if (! $this->equipo->admiteFrecuencia($frecuencia, $this->anio ?? now()->year)) {
            $fail(FrecuenciaInsuficienteException::MENSAJE);
        }
    }
}
