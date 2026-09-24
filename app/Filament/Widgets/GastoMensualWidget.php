<?php

namespace App\Filament\Widgets;

use App\Actions\CalcularGastoCorrectivoAction;
use App\Models\ConvenioEjecucionMensual;
use App\Support\PaletaGraficos;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

/**
 * RF-59 (Módulo 13): gasto mensual ejecutado, en barras apiladas — MP (suma de
 * `ConvenioEjecucionMensual.monto` del mes) y MC (suma de `MantenimientoCorrectivo.costo` del mes,
 * RN-04, vía `CalcularGastoCorrectivoAction::porMes()`). Mismas fuentes que el detalle de gasto
 * de RF-33, pero sin el filtro por plan: el Escritorio muestra el gasto de todos los convenios.
 *
 * Además del año en curso (lo que pide RF-59), se reutiliza el mismo filtro de año propio del
 * gráfico que `CumplimientoMpMensualWidget`, por consistencia entre ambos gráficos.
 */
class GastoMensualWidget extends ChartWidget
{
    /**
     * RF-57: ver el criterio de orden en `AlertasConveniosWidget::$sort`.
     */
    protected static ?int $sort = 31;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Gasto mensual ejecutado';

    protected ?string $description = 'Mantenimiento preventivo (convenios) y correctivo, por mes';

    protected ?string $maxHeight = '300px';

    private const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view.gasto_mensual_widget');
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
        return ConvenioEjecucionMensual::query()
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
        $anio = (int) $this->filter;

        $mpPorMes = ConvenioEjecucionMensual::query()
            ->where('anio', $anio)
            ->selectRaw('mes, sum(monto) as total')
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $mcPorMes = app(CalcularGastoCorrectivoAction::class)->porMes($anio);

        return [
            'datasets' => [
                [
                    'label' => 'Preventivo (MP)',
                    'data' => array_map(fn (int $mes): float => (float) ($mpPorMes[$mes] ?? 0), range(1, 12)),
                    'backgroundColor' => PaletaGraficos::PRIMARIO,
                    'borderColor' => PaletaGraficos::PRIMARIO,
                ],
                [
                    'label' => 'Correctivo (MC)',
                    'data' => array_values($mcPorMes),
                    'backgroundColor' => PaletaGraficos::ALERTA,
                    'borderColor' => PaletaGraficos::ALERTA,
                ],
            ],
            'labels' => self::MESES,
        ];
    }

    /**
     * Montos en pesos chilenos con separador de miles (RNF-10), en el eje y en el tooltip.
     */
    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                scales: {
                    x: { stacked: true },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => '$' + Number(value).toLocaleString('es-CL'),
                        },
                    },
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => context.dataset.label + ': $' + Number(context.parsed.y).toLocaleString('es-CL'),
                        },
                    },
                },
            }
        JS);
    }
}
