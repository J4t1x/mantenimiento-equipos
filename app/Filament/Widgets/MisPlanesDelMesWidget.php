<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoEjecucion;
use App\Filament\Resources\EjecucionesMensuales\EjecucionMensualResource;
use App\Models\EjecucionMensual;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * RF-45 (Módulo 11): carga de trabajo del mes en curso para el rol Técnico Interno, acotada a sus
 * propios planes asignados — mismo alcance que RF-20 (`App\Support\AlcanceTecnicoInterno`,
 * `PlanMantenimiento.responsable_interno_id`). A diferencia de `CumplimientoMpWidget`, que resume
 * el año completo de todos los equipos del sistema, este widget solo mira el mes en curso y solo
 * los planes del técnico conectado.
 *
 * RF-61 (Módulo 13): cada tarjeta enlaza a la Bitácora filtrada por el año en curso y por el
 * estado de la tarjeta. La Bitácora no tiene filtro por mes ni por responsable, así que el listado
 * muestra más filas que el número de la tarjeta (todo el año, todos los técnicos).
 */
class MisPlanesDelMesWidget extends StatsOverviewWidget
{
    /**
     * RF-57: ver el criterio de orden en `AlertasConveniosWidget::$sort`.
     */
    protected static ?int $sort = 12;

    private const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    protected function getHeading(): ?string
    {
        return 'Mis planes de '.self::MESES[now()->month];
    }

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view.mis_planes_del_mes_widget');
    }

    protected function getStats(): array
    {
        $anio = now()->year;
        $mes = now()->month;

        $porEstado = EjecucionMensual::query()
            ->whereHas('planMantenimiento', function ($query) use ($anio) {
                $query->where('responsable_interno_id', auth()->id())
                    ->where('anio', $anio);
            })
            ->where('mes', $mes)
            ->get()
            ->countBy(fn (EjecucionMensual $ejecucion) => $ejecucion->estado->value);

        $etiquetaMes = self::MESES[$mes];
        $urlBitacora = fn (EstadoEjecucion $estado): ?string => EjecucionMensualResource::canViewAny()
            ? EjecucionMensualResource::getUrl('index', ['filters' => [
                'anio' => ['value' => $anio],
                'estado' => ['value' => $estado->value],
            ]])
            : null;

        return [
            Stat::make('Pendientes', $porEstado[EstadoEjecucion::Programado->value] ?? 0)
                ->description("Programadas para {$etiquetaMes}")
                ->icon(Heroicon::OutlinedClock)
                ->color('info')
                ->url($urlBitacora(EstadoEjecucion::Programado)),

            Stat::make('Realizadas', $porEstado[EstadoEjecucion::Realizado->value] ?? 0)
                ->description(ucfirst($etiquetaMes))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->url($urlBitacora(EstadoEjecucion::Realizado)),

            Stat::make('Reprogramadas', $porEstado[EstadoEjecucion::Reprogramado->value] ?? 0)
                ->description(ucfirst($etiquetaMes))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->url($urlBitacora(EstadoEjecucion::Reprogramado)),
        ];
    }
}
