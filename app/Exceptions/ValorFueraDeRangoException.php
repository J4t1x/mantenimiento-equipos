<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * SPEC-ME-015 (hallazgo M-01): guarda de última instancia a nivel de modelo para montos negativos
 * y fechas de hechos ocurridos que quedan en el futuro. Los formularios validan lo mismo antes de
 * guardar; esta excepción cubre las demás vías (importador, tinker, seeders, una futura API).
 */
class ValorFueraDeRangoException extends RuntimeException implements ExcepcionDeDominio
{
    public static function montoNegativo(string $etiqueta): self
    {
        return new self("El campo {$etiqueta} no puede ser negativo.");
    }

    public static function fechaFutura(string $etiqueta): self
    {
        return new self("El campo {$etiqueta} no puede ser una fecha futura.");
    }
}
