<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Propiedad: string implements HasLabel
{
    case Propio = 'propio';
    case Arriendo = 'arriendo';
    case Comodato = 'comodato';
    case Prestamo = 'prestamo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Propio => 'Propio',
            self::Arriendo => 'Arriendo',
            self::Comodato => 'Comodato',
            self::Prestamo => 'Préstamo',
        };
    }
}
