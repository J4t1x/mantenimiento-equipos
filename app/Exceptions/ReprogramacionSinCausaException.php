<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * RF-66 del SRS (RN-09 del BRIEF, Res. Ex. 1341/2017 §7.4): una mantención no ejecutada se
 * justifica formalmente, así que un mes "Reprogramado" exige su causa en observaciones. Guarda de
 * última instancia a nivel de modelo (RNF-04, mismo criterio que
 * {@see TransicionBitacoraInvalidaException} para RN-02).
 */
class ReprogramacionSinCausaException extends RuntimeException
{
    public const MENSAJE = 'Indica la causa de la reprogramación en observaciones (Res. Ex. 1341/2017).';

    public static function porFaltaDeCausa(): self
    {
        return new self(self::MENSAJE);
    }
}
