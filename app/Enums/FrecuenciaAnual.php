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

    /**
     * Meses que quedan "Programado" con esta frecuencia (RN-01): uniformemente espaciados y
     * terminando en diciembre (frecuencia 4 → marzo, junio, septiembre y diciembre). Lo usan la
     * generación del plan (`GenerarPlanAnualAction`) y la vista previa del formulario (RF-83).
     *
     * @return array<int, int>
     */
    public function mesesProgramados(): array
    {
        $frecuencia = (int) $this->value;
        $paso = intdiv(12, $frecuencia);

        return array_map(fn (int $i): int => $i * $paso, range(1, $frecuencia));
    }

    /**
     * Normaliza la frecuencia tal como llega de un formulario de Filament o del modelo (instancia
     * del enum, string o entero).
     */
    public static function desde(mixed $valor): ?self
    {
        if ($valor instanceof self) {
            return $valor;
        }

        return filled($valor) ? self::tryFrom((string) $valor) : null;
    }
}
