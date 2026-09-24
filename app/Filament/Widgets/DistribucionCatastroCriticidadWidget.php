<?php

namespace App\Filament\Widgets;

use App\Enums\Criticidad;
use App\Models\Equipo;
use App\Support\PaletaGraficos;
use Filament\Widgets\ChartWidget;

/**
 * RF-60 (Módulo 13): composición del catastro activo por criticidad (EQC/EQR/IM≥12/No aplica), en
 * dona — pareja de `DistribucionCatastroWidget` (por estado), con el mismo permiso
 * (`view.distribucion_catastro_widget`); por eso esta clase está excluida de la generación de
 * permisos de Shield (`config/filament-shield.php`).
 */
class DistribucionCatastroCriticidadWidget extends ChartWidget
{
    /**
     * RF-57: ver el criterio de orden en `AlertasConveniosWidget::$sort`.
     */
    protected static ?int $sort = 33;

    protected ?string $heading = 'Catastro por criticidad';

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
        $porCriticidad = Equipo::query()
            ->where('activo', true)
            ->selectRaw('criticidad, count(*) as total')
            ->groupBy('criticidad')
            ->toBase()
            ->pluck('total', 'criticidad');

        $criticidades = Criticidad::cases();

        return [
            'datasets' => [
                [
                    'data' => array_map(fn (Criticidad $criticidad): int => (int) ($porCriticidad[$criticidad->value] ?? 0), $criticidades),
                    'backgroundColor' => array_map(fn (Criticidad $criticidad): string => PaletaGraficos::hex($criticidad->getColor()), $criticidades),
                ],
            ],
            'labels' => array_map(fn (Criticidad $criticidad): string => $criticidad->getLabel(), $criticidades),
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
