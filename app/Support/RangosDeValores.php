<?php

namespace App\Support;

/**
 * SPEC-ME-015 (FR-1): rangos admitidos en los formularios, compartidos por los recursos y los
 * relation managers que repiten los mismos campos.
 */
final class RangosDeValores
{
    public const ANIO_MINIMO = 1990;

    /**
     * Mensaje de las fechas de hechos ocurridos (`maxDate(now())`). El de Laravel mostraría la
     * fecha límite con hora ("anterior o igual a 2026-10-15 23:59:59").
     */
    public const MENSAJE_FECHA_FUTURA = 'La fecha no puede ser posterior a hoy.';

    public const VIDA_UTIL_MINIMA = 1;

    public const VIDA_UTIL_MAXIMA = 50;

    /**
     * La garantía de un equipo nuevo puede vencer varios años después de la compra (en la planilla
     * de Coyhaique hay garantías hasta 2028), así que su año admite más margen que el resto.
     */
    public const ANIOS_FUTUROS_GARANTIA = 15;

    /**
     * Año máximo para planes, ejecuciones de convenio, adquisición e ingreso al plan: el año
     * siguiente, para poder preparar el programa del próximo año.
     */
    public static function anioMaximo(): int
    {
        return now()->year + 1;
    }

    public static function anioMaximoGarantia(): int
    {
        return now()->year + self::ANIOS_FUTUROS_GARANTIA;
    }
}
