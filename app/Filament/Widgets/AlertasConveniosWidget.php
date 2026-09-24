<?php

namespace App\Filament\Widgets;

use App\Actions\CalcularEjecucionConvenioAction;
use App\Filament\Resources\Convenios\ConvenioResource;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * RF-43 (Módulo 11): alertas agregadas de convenios — sobregiro (RN-05) y vencimiento próximo —
 * hoy solo visibles entrando a la ficha o listado de cada convenio, uno por uno. Visible para
 * Encargado de Mantención y Encargado de Convenios (`view.alertas_convenios_widget`).
 *
 * RF-61 (Módulo 13): ambas tarjetas enlazan al listado de Convenios. "Por vencer" llega filtrado
 * por vigencia "Vigente" (el filtro más cercano que tiene el listado); "Sobregirados" llega sin
 * filtro, porque el listado no tiene uno de sobregiro — la columna de ejecución ya marca en rojo
 * los convenios sobregirados (RN-05).
 */
class AlertasConveniosWidget extends StatsOverviewWidget
{
    /**
     * RF-57 (Módulo 13): orden del Escritorio por jerarquía de uso — alertas (10-19), cumplimiento
     * MP (20-29), gráficos (30-39, reservado para RF-58 a RF-60) y resumen de contexto (40-49).
     * `AccountWidget` de Filament ya trae `$sort = -3`, así que queda primero como saludo.
     */
    protected static ?int $sort = 10;

    protected ?string $heading = 'Convenios que requieren atención';

    private const DIAS_POR_VENCER = 60;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view.alertas_convenios_widget');
    }

    protected function getStats(): array
    {
        $accion = app(CalcularEjecucionConvenioAction::class);

        $sobregirados = $accion->contarSobregirados(now()->year);
        $porVencer = $accion->contarPorVencer(self::DIAS_POR_VENCER);
        $puedeVerConvenios = ConvenioResource::canViewAny();

        return [
            Stat::make('Convenios sobregirados', $sobregirados)
                ->description('Ejecución '.now()->year.' supera el monto anual (RN-05)')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($sobregirados > 0 ? 'danger' : 'success')
                ->url($puedeVerConvenios ? ConvenioResource::getUrl('index') : null),

            Stat::make('Convenios por vencer', $porVencer)
                ->description('Vencen en los próximos '.self::DIAS_POR_VENCER.' días')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color($porVencer > 0 ? 'warning' : 'success')
                ->url($puedeVerConvenios ? ConvenioResource::getUrl('index', ['filters' => ['vigencia' => ['value' => 'vigente']]]) : null),
        ];
    }
}
