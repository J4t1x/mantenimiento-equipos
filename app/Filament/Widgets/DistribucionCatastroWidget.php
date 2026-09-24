<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoEquipo;
use App\Models\Equipo;
use App\Support\PaletaGraficos;
use Filament\Widgets\ChartWidget;

/**
 * RF-60 (Módulo 13): composición del catastro activo por estado (Bueno/Regular/Malo/Sin evaluar,
 * RF-12), en dona — hoy solo se conoce filtrando el listado de Equipos valor por valor. El color
 * de cada segmento es el mismo `getColor()` del enum que usa el badge de estado del listado.
 *
 * La dona por criticidad vive en `DistribucionCatastroCriticidadWidget` (un `ChartWidget` dibuja
 * un solo gráfico) y comparte el permiso de este widget, `view.distribucion_catastro_widget`.
 */
class DistribucionCatastroWidget extends ChartWidget
{
    /**
     * RF-57: ver el criterio de orden en `AlertasConveniosWidget::$sort`.
     */
    protected static ?int $sort = 32;

    protected ?string $heading = 'Catastro por estado';

    protected ?string $description = 'Equipos activos';

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view.distribucion_catastro_widget');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $porEstado = Equipo::query()
            ->where('activo', true)
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->toBase()
            ->pluck('total', 'estado');

        $estados = EstadoEquipo::cases();

        return [
            'datasets' => [
                [
                    'data' => array_map(fn (EstadoEquipo $estado): int => (int) ($porEstado[$estado->value] ?? 0), $estados),
                    'backgroundColor' => array_map(fn (EstadoEquipo $estado): string => PaletaGraficos::hex($estado->getColor()), $estados),
                ],
            ],
            'labels' => array_map(fn (EstadoEquipo $estado): string => $estado->getLabel(), $estados),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }
}
