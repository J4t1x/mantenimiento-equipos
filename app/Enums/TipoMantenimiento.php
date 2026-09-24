<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoMantenimiento: string implements HasLabel
{
    case Interno = 'interno';
    case Externo = 'externo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Interno => 'Interno',
            self::Externo => 'Externo',
        };
    }
}
