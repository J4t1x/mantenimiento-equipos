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
