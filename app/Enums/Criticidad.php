<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Criticidad: string implements HasColor, HasLabel
{
    case Critico = 'critico';
    case Relevante = 'relevante';
    case ImMayorIgual12 = 'im_mayor_igual_12';
    case NoAplica = 'no_aplica';

    public function getLabel(): string
    {
        return match ($this) {
            self::Critico => 'Crítico',
            self::Relevante => 'Relevante',
            self::ImMayorIgual12 => 'IM ≥ 12',
            self::NoAplica => 'No aplica',
        };
    }

    /**
     * RN-08 del BRIEF (Res. Ex. 1341/2017 del MINSAL, §7.4): el equipamiento crítico recibe MP al
     * menos dos veces al año. El resto de las criticidades no tiene mínimo normativo.
     */
    public function frecuenciaMinima(): int
    {
        return match ($this) {
            self::Critico => 2,
            default => 1,
        };
    }

    public function admiteFrecuencia(FrecuenciaAnual $frecuencia): bool
    {
        return (int) $frecuencia->value >= $this->frecuenciaMinima();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Critico => 'danger',
            self::Relevante => 'warning',
            self::ImMayorIgual12 => 'info',
            self::NoAplica => 'gray',
        };
    }
}
