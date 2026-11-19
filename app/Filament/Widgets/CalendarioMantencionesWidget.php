<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PlanMantenimientos\PlanMantenimientoResource;
use App\Filament\Support\CalendarioDeMantenciones;
use App\Filament\Support\RecibePeriodoDelEscritorio;
use Filament\Widgets\Widget;

/**
 * SPEC-ME-023: copia del calendario de mantenciones en el Escritorio.
 *
 * Usa la misma lógica y vista que la página del recurso ({@see CalendarioDeMantenciones}), pero el
 * año lo fija el selector del Escritorio y la vista anual muestra solo los meses del período elegido
 * (año, semestre, trimestre o mes). Se puede abrir un mes y recorrerlo dentro del año.
 */
class CalendarioMantencionesWidget extends Widget
{
    use CalendarioDeMantenciones;
    use RecibePeriodoDelEscritorio;

    protected string $view = 'filament.widgets.calendario-mantenciones-widget';

    protected int|string|array $columnSpan = 'full';

    public ?int $mes = null;

    public ?string $servicio = null;

    public ?string $criticidad = null;

    public ?string $tipo = null;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view.calendario_mantenciones_widget')
            && PlanMantenimientoResource::canViewAny();
    }

    public function anioCalendario(): int
    {
        return $this->anioSeleccionado();
    }

    public function puedeCambiarDeAnio(): bool
    {
        return false;
    }

    /**
     * @return array<int, int>
     */
    public function mesesVisibles(): array
    {
        return $this->periodoSeleccionado()->meses();
    }

    public function urlCalendarioCompleto(): string
    {
        return PlanMantenimientoResource::getUrl('calendario', array_filter([
            'anio' => $this->anioCalendario(),
            'mes' => $this->mes,
        ]));
    }
}
