<?php

namespace App\Filament\Support;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * SPEC-ME-007: los widgets de alertas del Escritorio muestran solo las tarjetas con casos, ordenadas
 * por gravedad (rojo antes que amarillo). Una alerta en 0 no requiere acción y solo agrega ruido.
 * Si no queda ninguna, se muestra una sola tarjeta "Sin alertas pendientes".
 */
class SoloAlertasPendientes
{
    private const ORDEN_POR_COLOR = ['danger' => 0, 'warning' => 1];

    /**
     * @param  array<int, Stat>  $alertas
     * @return array<int, Stat>
     */
    public static function filtrar(array $alertas): array
    {
        $pendientes = collect($alertas)
            ->filter(fn (Stat $alerta): bool => is_numeric($alerta->getValue()) && $alerta->getValue() > 0)
            ->sortBy(fn (Stat $alerta): int => self::ORDEN_POR_COLOR[$alerta->getColor()] ?? 2)
            ->values()
            ->all();

        return $pendientes !== [] ? $pendientes : [
            Stat::make('Sin alertas pendientes', 'Todo al día')
                ->description('No hay casos que requieran atención')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
        ];
    }
}
