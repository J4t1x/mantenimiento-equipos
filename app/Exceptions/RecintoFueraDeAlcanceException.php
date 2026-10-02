<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * RF-63 del SRS: un usuario con recintos asignados no puede registrar datos de otro recinto.
 * Guarda de última instancia a nivel de modelo (RNF-04, mismo criterio que
 * {@see EquipoNoAsignadoException} para RF-20): se lanza sin importar si el intento vino de
 * Filament, tinker o una futura API.
 */
class RecintoFueraDeAlcanceException extends RuntimeException
{
    public static function paraRecinto(): self
    {
        return new self('No puedes registrar datos de un recinto que no tienes asignado (RF-63).');
    }

    public static function paraEquipo(): self
    {
        return new self('No puedes registrar datos de un equipo de un recinto que no tienes asignado (RF-63).');
    }
}
