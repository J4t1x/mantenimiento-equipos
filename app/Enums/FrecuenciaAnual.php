<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FrecuenciaAnual: string implements HasLabel
{
    case Una = '1';
    case Dos = '2';
    case Tres = '3';
    case Cuatro = '4';
    case Seis = '6';
    case Doce = '12';

    public function getLabel(): string
    {
        return match ($this) {
            self::Una => '1 vez al año',
            self::Dos => '2 veces al año',
            self::Tres => '3 veces al año',
            self::Cuatro => '4 veces al año',
            self::Seis => '6 veces al año',
            self::Doce => '12 veces al año',
        };
    }
}
