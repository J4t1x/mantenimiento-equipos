<?php

namespace App\Support;

use Filament\Support\Colors\Color;

/**
 * Escala de tonos de Filament (50–950) para un color institucional exacto.
 *
 * `Color::hex()` de Filament solo conserva el matiz del color y aplica luminosidades fijas: con el
 * azul institucional `#0060AF` el tono 600 queda en un azul claro que no alcanza contraste AA con
 * texto blanco, y Filament termina pintando los botones principales en celeste con texto oscuro.
 * Aquí el tono 600 es el color exacto (el que usan los botones y elementos principales) y el resto
 * de la escala se deriva de él: los tonos claros mezclando con blanco y los oscuros escalando el
 * color, igual que la paleta v3 de Filament (`Color::generateV3Palette()`).
 */
class PaletaInstitucional
{
    private const MEZCLA_CON_BLANCO = [
        50 => 0.95,
        100 => 0.9,
        200 => 0.75,
        300 => 0.6,
        400 => 0.35,
        500 => 0.15,
    ];

    private const ESCALA_OSCURA = [
        700 => 0.85,
        800 => 0.7,
        900 => 0.55,
        950 => 0.38,
    ];

    /**
     * @return array<int, string>
     */
    public static function escala(string $hex): array
    {
        [$rojo, $verde, $azul] = sscanf(str_replace(' ', '', Color::convertToRgb($hex)), 'rgb(%d,%d,%d)');

        $escala = [];

        foreach (self::MEZCLA_CON_BLANCO as $tono => $mezcla) {
            $escala[$tono] = self::oklch(
                $rojo + (255 - $rojo) * $mezcla,
                $verde + (255 - $verde) * $mezcla,
                $azul + (255 - $azul) * $mezcla,
            );
        }

        $escala[600] = Color::convertToOklch($hex);

        foreach (self::ESCALA_OSCURA as $tono => $factor) {
            $escala[$tono] = self::oklch($rojo * $factor, $verde * $factor, $azul * $factor);
        }

        ksort($escala);

        return $escala;
    }

    private static function oklch(float $rojo, float $verde, float $azul): string
    {
        return Color::convertToOklch(sprintf('%d, %d, %d', round($rojo), round($verde), round($azul)));
    }
}
