<?php

namespace App\Support;

/**
 * Módulo 13 (RF-58 a RF-60): colores de los gráficos del Escritorio, en hexadecimal.
 *
 * Chart.js no entiende los colores `oklch()` con que Filament define su paleta (los usa para
 * calcular el color de hover), así que no se le pueden pasar tal cual. Estos valores replican la
 * paleta del panel (`AdminPanelProvider`): `PRIMARIO` y `PELIGRO` son el azul y el rojo
 * institucionales, y el resto es el tono 500 de la paleta de Filament asignada a cada color
 * semántico (`success` = Emerald, `warning` = Amber, `info` = Sky, `gray` = Slate), convertido a
 * hex.
 */
class PaletaGraficos
{
    public const PRIMARIO = '#0060AF';

    public const PRIMARIO_TENUE = 'rgba(0, 96, 175, 0.3)';

    public const PELIGRO = '#E2001A';

    public const EXITO = '#00BC7D';

    public const ALERTA = '#FE9A00';

    public const INFO = '#00A6F4';

    public const GRIS = '#62748E';

    /**
     * Traduce un color semántico de Filament (`success`, `warning`, `danger`, `info`, `gray`, `primary`) al
     * hex equivalente — permite reutilizar los `getColor()` de los enums y
     * `CumplimientoMpWidget::colorParaPorcentaje()` sin repetir la semántica aquí.
     */
    public static function hex(string $colorSemantico): string
    {
        return match ($colorSemantico) {
            'primary' => self::PRIMARIO,
            'danger' => self::PELIGRO,
            'success' => self::EXITO,
            'warning' => self::ALERTA,
            'info' => self::INFO,
            default => self::GRIS,
        };
    }
}
