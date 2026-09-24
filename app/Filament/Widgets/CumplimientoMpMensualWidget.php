<?php

namespace App\Filament\Widgets;

use App\Actions\CalcularCumplimientoMpAction;
use App\Models\PlanMantenimiento;
use App\Support\PaletaGraficos;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

/**
 * RF-58 (Módulo 13): tendencia mensual del cumplimiento MP (RN-03) — barras de programados vs.
 * ejecutados por mes y una línea con el % de cumplimiento acumulado a cada mes, mismo criterio que
 * `CalcularCumplimientoMpAction` (vía `porMes()`). Los puntos de la línea se colorean con los
 * umbrales de `CumplimientoMpWidget` (≥80% éxito, 50-79% alerta, <50% crítico).
 *
 * El año se elige con el filtro propio del gráfico (`getFilters()`, una propiedad Livewire del
 * widget), no con `HasFiltersForm` del Escritorio: este mecanismo no produce el error 500 de
 * Filament 5.7.8 + Livewire 4.4.3 descrito en `CumplimientoMpWidget`, así que es la única vía hoy
 * disponible para mirar el cumplimiento de otro año desde el Escritorio.
 *
 * Mismo permiso que `CumplimientoMpWidget` (`view.cumplimiento_mp_widget`, lo pide así RF-58); por
 * eso esta clase está excluida de la generación de permisos de Shield (`config/filament-shield.php`).
 */
class CumplimientoMpMensualWidget extends ChartWidget
{
    /**
     * RF-57: ver el criterio de orden en `AlertasConveniosWidget::$sort`.
     */
    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Cumplimiento MP mes a mes';

    protected ?string $description = 'Programados vs. ejecutados por mes, con el % de cumplimiento acumulado';

    protected ?string $maxHeight = '300px';

    private const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view.cumplimiento_mp_widget');
    }

    public function mount(): void
    {
        $this->filter ??= (string) now()->year;

        parent::mount();
    }

    /**
     * @return array<string, string>
     */
    protected function getFilters(): ?array
    {
        return PlanMantenimiento::query()
            ->distinct()
            ->pluck('anio')
            ->map(fn ($anio): int => (int) $anio)
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn (int $anio): array => [(string) $anio => (string) $anio])
            ->all();
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $meses = app(CalcularCumplimientoMpAction::class)->porMes((int) $this->filter);

        $programadosAcumulados = 0;
        $ejecutadosAcumulados = 0;
        $porcentajeAcumulado = [];

        foreach ($meses as $mes) {
            $programadosAcumulados += $mes['programados'];
            $ejecutadosAcumulados += $mes['ejecutados'];

            $porcentajeAcumulado[] = $programadosAcumulados > 0
                ? round($ejecutadosAcumulados / $programadosAcumulados * 100, 1)
                : null;
        }

        return [
            'datasets' => [
                [
                    'type' => 'line',
                    'label' => '% cumplimiento acumulado',
                    'data' => $porcentajeAcumulado,
                    'yAxisID' => 'porcentaje',
                    'borderColor' => PaletaGraficos::GRIS,
                    'pointBackgroundColor' => array_map(
                        fn (?float $porcentaje): string => PaletaGraficos::hex(CumplimientoMpWidget::colorParaPorcentaje($porcentaje)),
                        $porcentajeAcumulado,
                    ),
                    'pointRadius' => 5,
                    'spanGaps' => true,
                ],
                [
                    'label' => 'Programados',
                    'data' => array_column($meses, 'programados'),
                    'backgroundColor' => PaletaGraficos::PRIMARIO_TENUE,
                    'borderColor' => PaletaGraficos::PRIMARIO,
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Ejecutados',
                    'data' => array_column($meses, 'ejecutados'),
                    'backgroundColor' => PaletaGraficos::PRIMARIO,
                    'borderColor' => PaletaGraficos::PRIMARIO,
                ],
            ],
            'labels' => self::MESES,
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        title: { display: true, text: 'Mantenciones' },
                    },
                    porcentaje: {
                        position: 'right',
                        min: 0,
                        max: 100,
                        grid: { drawOnChartArea: false },
                        ticks: { callback: (value) => value + '%' },
                    },
                },
            }
        JS);
    }
}
