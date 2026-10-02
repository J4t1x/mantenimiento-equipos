<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * RF-65/RF-69 del SRS (RN-08 del BRIEF): un equipo crítico bajo plan de MP no puede tener una
 * frecuencia anual menor que 2, salvo con garantía vigente (periodicidad del fabricante). Guarda de última instancia a nivel de modelo (RNF-04, mismo criterio que
 * {@see TransicionBitacoraInvalidaException} para RN-02).
 */
class FrecuenciaInsuficienteException extends RuntimeException
{
    public const MENSAJE = 'Un equipo crítico debe tener mantenimiento preventivo al menos 2 veces al año, '
        .'salvo que tenga garantía vigente y siga la periodicidad del fabricante (Res. Ex. 1341/2017 del MINSAL).';

    public static function paraEquipoCritico(): self
    {
        return new self(self::MENSAJE);
    }
}
