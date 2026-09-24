<?php

namespace App\Filament\Widgets;

use App\Models\Convenio;
use App\Models\ConvenioEjecucionMensual;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * RF-44 (Módulo 11): panorama general de convenios para el rol Encargado de Convenios — hoy este
 * rol no ve ningún widget al entrar al Escritorio, porque `canView()` de los dos widgets
 * existentes exige permisos (`view.indicadores_generales_widget`, `view.cumplimiento_mp_widget`)
 * que `RolesAndPermissionsSeeder` nunca le asigna. Visible vía `view.convenios_encargado_widget`.
 */
class ConveniosEncargadoWidget extends StatsOverviewWidget
{
    /**
     * RF-57: ver el criterio de orden en `AlertasConveniosWidget::$sort`.
     */
    protected static ?int $sort = 40;

    protected ?string $heading = 'Resumen de convenios';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view.convenios_encargado_widget');
    }

    protected function getStats(): array
    {
        $anio = now()->year;

        $montoComprometido = (float) Convenio::where('activo', true)->sum('monto_anual');

        $montoEjecutado = (float) ConvenioEjecucionMensual::where('anio', $anio)
            ->whereHas('convenio', fn ($query) => $query->where('activo', true))
            ->sum('monto');

        $porcentaje = $montoComprometido > 0 ? round($montoEjecutado / $montoComprometido * 100, 1) : null;

        return [
            Stat::make('Convenios activos', Convenio::where('activo', true)->count())
                ->description('Vigentes con proveedores')
                ->icon(Heroicon::OutlinedDocumentCheck)
                ->color('primary'),

            Stat::make('Ejecución '.$anio, $porcentaje === null ? 'Sin monto comprometido' : "{$porcentaje}%")
                ->description(
                    '$'.number_format($montoEjecutado, 0, ',', '.').
                    ' de $'.number_format($montoComprometido, 0, ',', '.').' comprometido'
                )
                ->icon(Heroicon::OutlinedBanknotes)
                ->color($porcentaje !== null && $porcentaje > 100 ? 'danger' : 'success'),
        ];
    }
}
