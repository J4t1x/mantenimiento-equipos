<?php

namespace App\Filament\Support;

use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Models\PlanMantenimiento;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * RF-83 (Módulo 19): vista previa, en el formulario del plan, de los meses que quedarán
 * "Programado" con la frecuencia elegida, antes de guardar. Usa la misma distribución que la
 * generación del plan ({@see FrecuenciaAnual::mesesProgramados()}, RN-01).
 *
 * Al editar, mientras la frecuencia no cambie, muestra los meses que el plan ya tiene. Si cambia,
 * muestra cómo quedará y advierte que los meses ya realizados o reprogramados se conservan (la
 * regeneración no los toca, RF-15). Lo usan el formulario de Planificación MP y el relation
 * manager de planes del equipo; ambos necesitan la frecuencia con `->live()`.
 */
class VistaPreviaPlanAnual
{
    public static function make(): TextEntry
    {
        return TextEntry::make('vista_previa_meses')
            ->label(fn (Get $get, ?Model $record): string => match (true) {
                ! $record instanceof PlanMantenimiento => 'Meses que se programarán',
                self::cambiaLaFrecuencia($get, $record) => 'Meses que se programarán al guardar',
                default => 'Meses programados actualmente',
            })
            ->state(fn (Get $get, ?Model $record): string => $record instanceof PlanMantenimiento && ! self::cambiaLaFrecuencia($get, $record)
                ? self::mesesActuales($record)
                : self::descripcion(FrecuenciaAnual::desde($get('frecuencia_anual'))))
            ->helperText(fn (Get $get, ?Model $record): ?string => match (true) {
                ! $record instanceof PlanMantenimiento => null,
                self::cambiaLaFrecuencia($get, $record) => self::mesesQueSeConservan($record),
                default => 'Los meses solo se recalculan si cambias la frecuencia.',
            })
            ->columnSpanFull();
    }

    /**
     * El plan solo se regenera al cambiar la frecuencia (`PlanMantenimientoObserver`). Mientras no
     * cambie, lo que corresponde mostrar son los meses que el plan ya tiene, que en los planes
     * importados vienen de la planilla y pueden no coincidir con el reparto uniforme.
     */
    private static function cambiaLaFrecuencia(Get $get, PlanMantenimiento $plan): bool
    {
        return FrecuenciaAnual::desde($get('frecuencia_anual')) !== FrecuenciaAnual::desde($plan->frecuencia_anual);
    }

    private static function mesesActuales(PlanMantenimiento $plan): string
    {
        $meses = $plan->ejecucionesMensuales()
            ->where('estado', '!=', EstadoEjecucion::SinProgramar)
            ->orderBy('mes')
            ->pluck('mes')
            ->map(fn (int $mes): string => mb_strtolower(BitacoraMensual::MESES[$mes]))
            ->all();

        if ($meses === []) {
            return 'Sin meses programados.';
        }

        return ucfirst(Arr::join($meses, ', ', ' y ')).' ('.(count($meses) === 1 ? '1 mantención' : count($meses).' mantenciones').')';
    }

    public static function descripcion(?FrecuenciaAnual $frecuencia): string
    {
        if ($frecuencia === null) {
            return 'Elige la frecuencia anual para ver los meses.';
        }

        $meses = array_map(
            fn (int $mes): string => mb_strtolower(BitacoraMensual::MESES[$mes]),
            $frecuencia->mesesProgramados(),
        );

        $cantidad = count($meses) === 1 ? '1 mantención' : count($meses).' mantenciones';

        return ucfirst(Arr::join($meses, ', ', ' y ')).' ('.$cantidad.')';
    }

    private static function mesesQueSeConservan(PlanMantenimiento $plan): ?string
    {
        $registrados = $plan->ejecucionesMensuales()
            ->whereIn('estado', [EstadoEjecucion::Realizado, EstadoEjecucion::Reprogramado])
            ->orderBy('mes')
            ->get()
            ->map(fn ($ejecucion): string => mb_strtolower(BitacoraMensual::MESES[$ejecucion->mes]).' ('.mb_strtolower($ejecucion->estado->getLabel()).')')
            ->all();

        if ($registrados === []) {
            return null;
        }

        return 'Se conservan los meses ya registrados: '.Arr::join($registrados, ', ', ' y ').'.';
    }
}
