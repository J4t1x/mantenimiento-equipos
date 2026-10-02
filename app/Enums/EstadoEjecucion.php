<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Estados de la bitácora mensual (BRIEF §2): equivalentes a los símbolos X/√/ꓣ de la planilla.
 */
enum EstadoEjecucion: string implements HasColor, HasLabel
{
    case SinProgramar = 'sin_programar';
    case Programado = 'programado';
    case Realizado = 'realizado';
    case Reprogramado = 'reprogramado';

    public function getLabel(): string
    {
        return match ($this) {
            self::SinProgramar => 'Sin programar',
            self::Programado => 'Programado',
            self::Realizado => 'Realizado',
            self::Reprogramado => 'Reprogramado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SinProgramar => 'gray',
            self::Programado => 'info',
            self::Realizado => 'success',
            self::Reprogramado => 'warning',
        };
    }

    /**
     * Normaliza el estado tal como llega de un formulario de Filament (instancia del enum o string).
     */
    public static function desde(mixed $valor): ?self
    {
        return $valor instanceof self ? $valor : self::tryFrom((string) $valor);
    }

    /**
     * RN-02 del BRIEF: un mes sin marca "Programado" previa no puede pasar directamente a
     * "Realizado" o "Reprogramado". Cualquier otra transición (incluidas correcciones entre
     * Realizado y Reprogramado) queda permitida.
     */
    public static function esTransicionValida(?self $desde, self $hacia): bool
    {
        $requierePreviaProgramacion = in_array($hacia, [self::Realizado, self::Reprogramado], true);

        if (! $requierePreviaProgramacion) {
            return true;
        }

        return ($desde ?? self::SinProgramar) !== self::SinProgramar;
    }
}
