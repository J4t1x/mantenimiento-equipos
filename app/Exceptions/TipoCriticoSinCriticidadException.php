<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * RF-75 del SRS (Res. Ex. 1341/2017, "Definiciones"): un equipo de uno de los 6 tipos que la norma
 * exige considerar críticos no puede tener otra criticidad. Guarda de última instancia a nivel de
 * modelo (RNF-04).
 */
class TipoCriticoSinCriticidadException extends RuntimeException
{
    public const MENSAJE = 'Este tipo de equipo es crítico según la Res. Ex. 1341/2017 del MINSAL: su criticidad debe ser "Crítico".';

    public static function paraTipo(): self
    {
        return new self(self::MENSAJE);
    }
}
