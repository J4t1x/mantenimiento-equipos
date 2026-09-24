<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * RN-02 del BRIEF: un mes sin marca "Programado" previa no puede pasar directamente a
 * "Realizado" o "Reprogramado". Guarda de última instancia a nivel de modelo (RNF-04,
 * "defensa en profundidad"): se lanza sin importar si el intento vino de Filament, tinker,
 * un seeder o una futura API.
 */
class TransicionBitacoraInvalidaException extends RuntimeException
{
    public static function porFaltaDeProgramacion(): self
    {
        return new self(
            'No se puede marcar como "Realizado" o "Reprogramado" un mes sin una marca '
            .'"Programado" previa (RN-02).'
        );
    }
}
