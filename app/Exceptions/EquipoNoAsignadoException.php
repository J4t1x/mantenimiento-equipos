<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * RF-20 del SRS: "El sistema debe permitir a un técnico interno... registrar únicamente la
 * ejecución de los equipos que tiene asignados". Guarda de última instancia a nivel de modelo
 * (RNF-04, "defensa en profundidad", mismo criterio que {@see TransicionBitacoraInvalidaException}
 * para RN-02): se lanza sin importar si el intento vino de Filament, tinker o una futura API.
 */
class EquipoNoAsignadoException extends RuntimeException
{
    public static function porFaltaDeAsignacion(): self
    {
        return new self(
            'No puedes registrar la ejecución de un equipo que no tienes asignado como '
            .'responsable interno (RF-20).'
        );
    }
}
