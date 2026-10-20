<?php

namespace App\Filament\Widgets;

use App\Actions\ObtenerDetalleGastoAction;
use App\Filament\Support\RecibePeriodoDelEscritorio;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * RF-81 (Módulo 19): % de gasto de mantenimiento preventivo ejecutado frente al programado, para el
 * año y período del Escritorio. Usa `ObtenerDetalleGastoAction`, la misma fuente que la tarjeta
 * "Detalle de gasto" de Reportes (RF-33, programado del presupuesto del recinto según RF-76), así
 * que ambos muestran el mismo porcentaje. Junto al preventivo se muestra el correctivo, con el
 * mismo cálculo, porque sin él el gasto preventivo queda sin contexto.
 *
 * Reutiliza el permiso del gráfico de gasto mensual (`view.gasto_mensual_widget`: Encargado de
 * Mantención, Encargado de Convenios y Jefatura), así que está excluido de la generación de
 * permisos de Shield (`config/filament-shield.php`).
 */
class GastoMpWidget extends StatsOverviewWidget
{
    use RecibePeriodoDelEscritorio;

    /**
     * RF-57: ver el criterio de orden en `AlertasConveniosWidget::$sort`. Va con el cumplimiento MP.
     */
    protected static ?int $sort = 21;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view.gasto_mensual_widget');
    }

    protected function getHeading(): ?string
    {
        $periodo = $this->periodoSeleccionado();
        $anio = $this->anioSeleccionado();

        return 'Gasto de mantenimiento '.($periodo->esAnual() ? $anio : "· {$periodo->getLabel()} {$anio}");
    }

    protected function getStats(): array
    {
        $gasto = app(ObtenerDetalleGastoAction::class)->execute($this->anioSeleccionado(), periodo: $this->periodoSeleccionado());

        return [
            $this->stat('Gasto MP ejecutado', $gasto['mp']['ejecutado'], $gasto['mp']['programado'], $gasto['mp']['porcentaje'])
                ->icon(Heroicon::OutlinedBanknotes),
            $this->stat('Gasto MC ejecutado', $gasto['mc']['ejecutado'], $gasto['mc']['programado'], $gasto['mc']['porcentaje'])
                ->icon(Heroicon::OutlinedWrenchScrewdriver),
        ];
    }

    private function stat(string $etiqueta, float $ejecutado, ?float $programado, ?float $porcentaje): Stat
    {
        if ($porcentaje === null) {
            return Stat::make($etiqueta, 'Sin gasto programado')
                ->description(self::pesos($ejecutado).' ejecutado')
                ->color('gray');
        }

        return Stat::make($etiqueta, "{$porcentaje}%")
            ->description(self::pesos($ejecutado).' de '.self::pesos((float) $programado).' programado')
            ->color($porcentaje > 100 ? 'danger' : 'primary');
    }

    private static function pesos(float $monto): string
    {
        return '$'.number_format($monto, 0, ',', '.');
    }
}
