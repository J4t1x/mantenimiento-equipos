<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * RF-73 del SRS: un equipo no puede retirarse de uso dos veces sin reingreso intermedio, ni
 * reingresar si no está retirado.
 */
class RetiroUsoInvalidoException extends RuntimeException
{
    public static function yaRetirado(): self
    {
        return new self('El equipo ya está retirado de uso. Registra primero su reingreso.');
    }

    public static function sinRetiroAbierto(): self
    {
        return new self('El equipo no tiene un retiro de uso abierto.');
    }
}
