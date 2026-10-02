<?php

namespace App\Filament\Widgets;

use App\Actions\CalcularCumplimientoMpAction;
use App\Enums\Criticidad;
use App\Enums\Periodo;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

/**
 * RN-03 (RF-21/RF-22/RF-23): % de cumplimiento de MP = ejecutados/programados, desagregado en los
 * 4 cortes del BRIEF, para el año en curso.
 *
 * El RF-21 pide "un período seleccionado" (año o mes navegable) — se intentó con un selector en el
 * Escritorio (`Filament\Pages\Dashboard` + `HasFiltersForm`), pero **cualquier** campo de
 * formulario agregado ahí (probado con `Select` y `TextInput`, con y sin opciones dinámicas)
 * produce un error 500 (`trim(): Argument #1 must be of type string, array given` en
 * `ComponentAttributeBag.php:502`, al renderizar el `loading-section` del widget) — incompatibilidad
 * de esta combinación exacta Filament 5.7.8 + Livewire 4.4.3, no un error de esta app.
 *
 * RF-21 (cierre, Módulo 17): el rodeo está en `App\Filament\Pages\Escritorio`, que tiene su propio
 * formulario y pasa el período como dos propiedades escalares reactivas (`anio`, `periodo`) en vez
 * del array `pageFilters`. Sin página que las entregue (p. ej. en tests), el widget usa el año en
 * curso completo, como antes.
 *
 * Mejora de diseño (14-09-2026, sin cambios de cálculo): el color de cada tile pasa a representar
 * el **estado** del % de cumplimiento (bueno/alerta/crítico), no la identidad del corte — antes
 * EQC/EQR/IM≥12 tenían un color fijo sin relación con el valor, por lo que un corte en 100% se
 * veía igual de "alarmante" que uno en 10%. La identidad de cada corte queda solo en el texto de
 * la etiqueta, como corresponde. Umbrales (decisión técnica, no está en el BRIEF, a confirmar si
 * Cristian indica algo distinto): ≥80% éxito, 50-79% alerta, <50% crítico, sin datos programados
 * queda en gris.
 *
 * RF-61 (Módulo 13): la tarjeta "Total" lleva una mini-tendencia con el % de cumplimiento de cada
 * uno de los últimos 12 meses (cruza el cambio de año); los meses sin nada programado quedan como
 * hueco en la línea en vez de un 0% que no ocurrió.
 */
class CumplimientoMpWidget extends StatsOverviewWidget
{
    /**
     * RF-57: ver el criterio de orden en `AlertasConveniosWidget::$sort`.
     */
    protected static ?int $sort = 20;

    private const UMBRAL_EXITO = 80.0;

    private const UMBRAL_ALERTA = 50.0;

    #[Reactive]
    public ?int $anio = null;

    #[Reactive]
    public ?string $periodo = null;

    private function anioSeleccionado(): int
    {
        return $this->anio ?? now()->year;
    }

    private function periodoSeleccionado(): Periodo
    {
        return Periodo::tryFrom((string) $this->periodo) ?? Periodo::Anual;
    }

    protected function getHeading(): ?string
    {
        $periodo = $this->periodoSeleccionado();
        $anio = $this->anioSeleccionado();

        return 'Cumplimiento de mantenimiento preventivo '.($periodo->esAnual() ? $anio : "· {$periodo->getLabel()} {$anio}");
    }

    /**
     * RF-35 (BRIEF §5): visibilidad del widget restringida por permiso de rol.
     */
    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view.cumplimiento_mp_widget');
    }

    protected function getStats(): array
    {
        $accion = app(CalcularCumplimientoMpAction::class);
        $cortes = $accion->execute($this->anioSeleccionado(), periodo: $this->periodoSeleccionado());

        return [
            $this->stat('Total', $cortes['total'])
                ->chart($this->porcentajesUltimos12Meses($accion)),
            $this->stat('Equipos críticos (EQC)', $cortes[Criticidad::Critico->value]),
            $this->stat('Equipos relevantes (EQR)', $cortes[Criticidad::Relevante->value]),
            $this->stat('IM ≥ 12', $cortes[Criticidad::ImMayorIgual12->value]),
        ];
    }

    /**
     * @param  array{programados: int, ejecutados: int, porcentaje: float|null}  $corte
     */
    private function stat(string $label, array $corte): Stat
    {
        $porcentaje = $corte['porcentaje'];
        $valor = $porcentaje === null ? 'Sin datos' : "{$porcentaje}%";

        return Stat::make($label, $valor)
            ->description("{$corte['ejecutados']} de {$corte['programados']} programados")
            ->icon(self::iconoParaPorcentaje($porcentaje))
            ->color(self::colorParaPorcentaje($porcentaje));
    }

    /**
     * @return array<int, float|null>
     */
    private function porcentajesUltimos12Meses(CalcularCumplimientoMpAction $accion): array
    {
        $hoy = now();
        $anioAnterior = $accion->porMes($hoy->year - 1);
        $anioActual = $accion->porMes($hoy->year);

        $meses = [
            ...array_slice($anioAnterior, $hoy->month, preserve_keys: false),
            ...array_slice($anioActual, 0, $hoy->month, preserve_keys: false),
        ];

        return array_column($meses, 'porcentaje');
    }

    public static function colorParaPorcentaje(?float $porcentaje): string
    {
        return match (true) {
            $porcentaje === null => 'gray',
            $porcentaje >= self::UMBRAL_EXITO => 'success',
            $porcentaje >= self::UMBRAL_ALERTA => 'warning',
            default => 'danger',
        };
    }

    public static function iconoParaPorcentaje(?float $porcentaje): Heroicon
    {
        return match (true) {
            $porcentaje === null => Heroicon::OutlinedClipboardDocumentList,
            $porcentaje >= self::UMBRAL_EXITO => Heroicon::OutlinedCheckCircle,
            $porcentaje >= self::UMBRAL_ALERTA => Heroicon::OutlinedExclamationTriangle,
            default => Heroicon::OutlinedXCircle,
        };
    }
}
