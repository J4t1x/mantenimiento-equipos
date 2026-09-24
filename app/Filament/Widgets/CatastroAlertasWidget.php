<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoEquipo;
use App\Filament\Resources\Equipos\EquipoResource;
use App\Models\Equipo;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * RF-46 (Módulo 11): tres indicadores de catastro que hoy solo se ven equipo por equipo, en su
 * ficha — este widget los agrega para el Escritorio de Encargado de Mantención y Jefatura. Los
 * tres son consultas directas sobre `Equipo`, sin cálculo nuevo: "Sin evaluar" es un valor propio
 * de `estado` (RF-12), la garantía por vencer usa los mismos campos que muestra
 * `EquipoInfolist` (`en_garantia`/`garantia_anio_vencimiento`), y la vida útil residual replica en
 * SQL la misma fórmula del accessor `Equipo::vidaUtilResidual()` (RF-09) para poder filtrar por
 * ella sin cargar todo el catastro en memoria.
 *
 * RF-61 (Módulo 13): las tres tarjetas enlazan al listado de Equipos; "Sin evaluar" llega con el
 * filtro de estado ya aplicado. Garantía y vida útil llegan sin filtro, porque el listado no tiene
 * uno para esos campos.
 */
class CatastroAlertasWidget extends StatsOverviewWidget
{
    /**
     * RF-57: ver el criterio de orden en `AlertasConveniosWidget::$sort`.
     */
    protected static ?int $sort = 11;

    protected ?string $heading = 'Catastro que requiere atención';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view.catastro_alertas_widget');
    }

    protected function getStats(): array
    {
        $anio = now()->year;

        $sinEvaluar = Equipo::where('activo', true)
            ->where('estado', EstadoEquipo::SinEvaluar)
            ->count();

        $garantiaPorVencer = Equipo::where('activo', true)
            ->where('en_garantia', true)
            ->where('garantia_anio_vencimiento', $anio)
            ->count();

        $vidaUtilNegativa = Equipo::where('activo', true)
            ->whereNotNull('anio_adquisicion')
            ->whereNotNull('vida_util_anios')
            ->whereRaw('anio_adquisicion + vida_util_anios < ?', [$anio])
            ->count();

        $puedeVerEquipos = EquipoResource::canViewAny();
        $urlEquipos = $puedeVerEquipos ? EquipoResource::getUrl('index') : null;

        return [
            Stat::make('Equipos sin evaluar', $sinEvaluar)
                ->description('Estado "Sin evaluar" (RF-12)')
                ->icon(Heroicon::OutlinedQuestionMarkCircle)
                ->color($sinEvaluar > 0 ? 'warning' : 'success')
                ->url($puedeVerEquipos ? EquipoResource::getUrl('index', ['filters' => ['estado' => ['value' => EstadoEquipo::SinEvaluar->value]]]) : null),

            Stat::make('Garantía vence este año', $garantiaPorVencer)
                ->description("Vencimiento en {$anio}")
                ->icon(Heroicon::OutlinedShieldExclamation)
                ->color($garantiaPorVencer > 0 ? 'warning' : 'success')
                ->url($urlEquipos),

            Stat::make('Vida útil residual negativa', $vidaUtilNegativa)
                ->description('Superaron su vida útil estimada (RF-09)')
                ->icon(Heroicon::OutlinedExclamationCircle)
                ->color($vidaUtilNegativa > 0 ? 'danger' : 'success')
                ->url($urlEquipos),
        ];
    }
}
