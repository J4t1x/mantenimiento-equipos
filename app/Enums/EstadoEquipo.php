<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoEquipo: string implements HasColor, HasLabel
{
    case Bueno = 'bueno';
    case Regular = 'regular';
    case Malo = 'malo';
    case SinEvaluar = 'sin_evaluar';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bueno => 'Bueno',
            self::Regular => 'Regular',
            self::Malo => 'Malo',
            self::SinEvaluar => 'Sin evaluar',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Bueno => 'success',
            self::Regular => 'warning',
            self::Malo => 'danger',
            self::SinEvaluar => 'gray',
        };
    }
}
