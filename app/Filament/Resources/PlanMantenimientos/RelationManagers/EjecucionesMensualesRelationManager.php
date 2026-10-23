<?php

namespace App\Filament\Resources\PlanMantenimientos\RelationManagers;

use App\Enums\EstadoEjecucion;
use App\Filament\Support\BitacoraMensual;
use App\Models\EjecucionMensual;
use App\Support\AlcanceTecnicoInterno;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * RF-38 del SRS: la bitácora de un equipo se muestra como una cuadrícula compacta de 12 meses con
 * celdas coloreadas por estado y una leyenda visible, en vez de la tabla vertical de 12 filas que
 * tenía antes este relation manager.
 *
 * 23-10-2026: la vista se rehízo con estilos propios, porque las utilidades Tailwind no se compilan
 * para el panel y la grilla se veía como texto corrido. Suma un resumen (programadas, realizadas,
 * reprogramadas y cumplimiento), la fecha realizada en cada celda y el mes en curso destacado.
 *
 * RN-01 garantiza que un plan siempre tiene exactamente 12 `EjecucionMensual` (una por mes), así
 * que la grilla edita meses existentes — no crea ni elimina filas, a diferencia del CRUD genérico
 * que tenía antes este relation manager.
 */
class EjecucionesMensualesRelationManager extends RelationManager
{
    protected static string $relationship = 'ejecucionesMensuales';

    protected static ?string $title = 'Bitácora mensual';

    protected string $view = 'filament.resources.plan-mantenimientos.relation-managers.ejecuciones-mensuales-grid';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $plan = $this->getOwnerRecord();

        /** @var Collection<int, EjecucionMensual> $ejecucionesPorMes */
        $ejecucionesPorMes = $plan->ejecucionesMensuales()->orderBy('mes')->get()->keyBy('mes');

        $programadas = $ejecucionesPorMes->filter(fn (EjecucionMensual $ejecucion): bool => $ejecucion->estado !== EstadoEjecucion::SinProgramar)->count();
        $realizadas = $ejecucionesPorMes->filter(fn (EjecucionMensual $ejecucion): bool => $ejecucion->estado === EstadoEjecucion::Realizado)->count();

        return [
            'anio' => (int) $plan->anio,
            'ejecucionesPorMes' => $ejecucionesPorMes,
            'mesesAbreviados' => BitacoraMensual::MESES_ABREVIADOS,
            'mesesCompletos' => BitacoraMensual::MESES,
            'puedeGestionar' => AlcanceTecnicoInterno::puedeGestionarPlan($plan),
            // Mismo criterio que el indicador de cumplimiento (RN-03): programadas son los meses con
            // marca (programado, realizado o reprogramado).
            'resumen' => [
                'programadas' => $programadas,
                'realizadas' => $realizadas,
                'reprogramadas' => $ejecucionesPorMes->filter(fn (EjecucionMensual $ejecucion): bool => $ejecucion->estado === EstadoEjecucion::Reprogramado)->count(),
                'porcentaje' => $programadas > 0 ? round($realizadas / $programadas * 100, 1) : null,
            ],
        ];
    }

    public function editarMesAction(): Action
    {
        return Action::make('editarMes')
            ->label(fn (array $arguments): string => BitacoraMensual::MESES[$arguments['mes']] ?? 'Mes')
            ->modalHeading(fn (array $arguments): string => 'Bitácora — '.(BitacoraMensual::MESES[$arguments['mes']] ?? ''))
            ->modalSubmitActionLabel('Guardar')
            ->schema([
                // Portador del mes en el propio estado del formulario: los closures de `rule()` a
                // nivel de campo no reciben los `$arguments` de la acción montada, a diferencia de
                // `fillForm()`/`action()`.
                Hidden::make('mes'),
                ...BitacoraMensual::campos(fn (Get $get): ?EjecucionMensual => $this->buscarEjecucion((int) $get('mes'))),
            ])
            ->fillForm(fn (array $arguments): array => [
                'mes' => $arguments['mes'],
                ...BitacoraMensual::datos($this->buscarEjecucion($arguments['mes'])),
            ])
            ->action(fn (array $arguments, array $data) => BitacoraMensual::guardar($this->getOwnerRecord(), $arguments['mes'], $data))
            ->visible(fn (): bool => AlcanceTecnicoInterno::puedeGestionarPlan($this->getOwnerRecord()));
    }

    private function buscarEjecucion(int $mes): ?EjecucionMensual
    {
        return $this->getOwnerRecord()->ejecucionesMensuales()->where('mes', $mes)->first();
    }

    public function render(): View
    {
        return view($this->view, $this->getViewData());
    }
}
