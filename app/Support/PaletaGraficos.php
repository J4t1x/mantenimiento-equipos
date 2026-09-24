<?php

namespace App\Support;

/**
 * Módulo 13 (RF-58 a RF-60): colores de los gráficos del Escritorio, en hexadecimal.
 *
 * Chart.js no entiende los colores `oklch()` con que Filament define su paleta (los usa para
 * calcular el color de hover), así que no se le pueden pasar tal cual. Por la nota de alcance del
 * Módulo 13 ("sin introducir colores nuevos") estos valores no son una paleta propia: `PRIMARIO` y
 * `PELIGRO` son los mismos hex de `AdminPanelProvider`, y el resto es el tono 500 de la paleta
 * por defecto de Filament para cada color semántico (`success` = Green, `warning` = Amber,
 * `info` = Blue, `gray` = Zinc), convertido a hex.
 */
class PaletaGraficos
{
    public const PRIMARIO = '#006BBB';

    public const PRIMARIO_TENUE = 'rgba(0, 107, 187, 0.3)';

    public const PELIGRO = '#F10533';

    public const EXITO = '#00C950';

    public const ALERTA = '#FE9A00';

    public const INFO = '#2B7FFF';

    public const GRIS = '#9F9FA9';

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
