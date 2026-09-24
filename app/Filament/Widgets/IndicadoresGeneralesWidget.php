<?php

namespace App\Filament\Widgets;

use App\Enums\Criticidad;
use App\Models\Convenio;
use App\Models\Equipo;
use App\Models\MantenimientoCorrectivo;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Panorama general del escritorio: conteos reales simples (catastro, criticidad, convenios,
 * correctivos), sin el % de cumplimiento de MP del mockup — ese cálculo vive en
 * `CumplimientoMpWidget` (RN-03). La alerta de sobregiro de convenio (RN-05) vive en el listado y
 * la ficha de Convenios, no en este widget de escritorio.
 *
 * RF-61 (Módulo 13): "Correctivos este año" lleva una mini-tendencia con la cantidad de correctivos
 * de cada uno de los últimos 12 meses.
 */
class IndicadoresGeneralesWidget extends StatsOverviewWidget
{
    /**
     * RF-57: ver el criterio de orden en `AlertasConveniosWidget::$sort`.
     */
    protected static ?int $sort = 41;

    protected ?string $heading = 'Resumen general';

    /**
     * RF-35 (BRIEF §5): visibilidad del widget restringida por permiso de rol.
     */
    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view.indicadores_generales_widget');
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Equipos registrados', Equipo::where('activo', true)->count())
                ->description('Catastro activo')
                ->icon(Heroicon::OutlinedCube)
                ->color('primary'),

            Stat::make('Equipos críticos', Equipo::where('criticidad', Criticidad::Critico)->count())
                ->description('Criticidad "Crítico"')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),

            Stat::make('Convenios activos', Convenio::where('activo', true)->count())
                ->description('Vigentes con proveedores')
                ->icon(Heroicon::OutlinedDocumentCheck)
                ->color('success'),

            Stat::make('Correctivos este año', MantenimientoCorrectivo::whereYear('fecha', now()->year)->count())
                ->description((string) now()->year)
                ->icon(Heroicon::OutlinedWrenchScrewdriver)
                ->color('warning')
                ->chart($this->correctivosUltimos12Meses()),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function correctivosUltimos12Meses(): array
    {
        $desde = now()->startOfMonth()->subMonths(11);

        $porMes = MantenimientoCorrectivo::query()
            ->where('fecha', '>=', $desde)
            ->get(['fecha'])
            ->countBy(fn (MantenimientoCorrectivo $correctivo): string => $correctivo->fecha->format('Y-m'));

        return collect(range(0, 11))
            ->map(fn (int $desplazamiento): int => $porMes[$desde->copy()->addMonths($desplazamiento)->format('Y-m')] ?? 0)
            ->all();
    }
}
