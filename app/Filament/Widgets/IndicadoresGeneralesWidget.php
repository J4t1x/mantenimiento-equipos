<?php

namespace App\Filament\Widgets;

use App\Enums\Criticidad;
use App\Filament\Support\RecibePeriodoDelEscritorio;
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
 *
 * Período del Escritorio (corrección de RF-21, SPEC-ME-002): "Correctivos" cuenta los del año y
 * período elegidos, y su tendencia termina en el mes actual (año en curso) o en diciembre del año
 * elegido. Equipos, críticos y convenios activos son el estado actual y no dependen del año.
 */
class IndicadoresGeneralesWidget extends StatsOverviewWidget
{
    use RecibePeriodoDelEscritorio;

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

            Stat::make('Equipos críticos', Equipo::where('activo', true)->where('criticidad', Criticidad::Critico)->count())
                ->description('Activos con criticidad "Crítico"')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),

            Stat::make('Convenios activos', Convenio::where('activo', true)->count())
                ->description('Vigentes con proveedores')
                ->icon(Heroicon::OutlinedDocumentCheck)
                ->color('success'),

            Stat::make($this->etiquetaCorrectivos(), $this->contarCorrectivosDelPeriodo())
                ->description($this->periodoSeleccionado()->esAnual()
                    ? (string) $this->anioSeleccionado()
                    : "{$this->periodoSeleccionado()->getLabel()} {$this->anioSeleccionado()}")
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
        $hasta = $this->esAnioEnCurso() || $this->anioSeleccionado() > now()->year
            ? now()->startOfMonth()
            : now()->setDate($this->anioSeleccionado(), 12, 1)->startOfMonth();
        $desde = $hasta->copy()->subMonths(11);

        $porMes = MantenimientoCorrectivo::query()
            ->where('fecha', '>=', $desde)
            ->where('fecha', '<=', $hasta->copy()->endOfMonth())
            ->get(['fecha'])
            ->countBy(fn (MantenimientoCorrectivo $correctivo): string => $correctivo->fecha->format('Y-m'));

        return collect(range(0, 11))
            ->map(fn (int $desplazamiento): int => $porMes[$desde->copy()->addMonths($desplazamiento)->format('Y-m')] ?? 0)
            ->all();
    }

    private function etiquetaCorrectivos(): string
    {
        $periodo = $this->periodoSeleccionado();

        if ($periodo->esAnual()) {
            return $this->esAnioEnCurso() ? 'Correctivos este año' : "Correctivos {$this->anioSeleccionado()}";
        }

        return 'Correctivos del período';
    }

    private function contarCorrectivosDelPeriodo(): int
    {
        $periodo = $this->periodoSeleccionado();
        $anio = $this->anioSeleccionado();

        return MantenimientoCorrectivo::query()
            ->whereBetween('fecha', [$periodo->inicio($anio)->toDateString(), $periodo->fin($anio)->toDateString()])
            ->count();
    }
}
