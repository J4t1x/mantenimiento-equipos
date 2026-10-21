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
 * Al editar un plan con meses ya realizados o reprogramados, advierte que esos meses se conservan
 * (la regeneración no los toca, RF-15). Lo usan el formulario de Planificación MP y el relation
 * manager de planes del equipo; ambos necesitan la frecuencia con `->live()`.
 */
class VistaPreviaPlanAnual
{
    public static function make(): TextEntry
    {
        return TextEntry::make('vista_previa_meses')
            ->label('Meses que se programarán')
            ->state(fn (Get $get): string => self::descripcion(FrecuenciaAnual::desde($get('frecuencia_anual'))))
            ->helperText(fn (?Model $record): ?string => $record instanceof PlanMantenimiento ? self::mesesQueSeConservan($record) : null)
            ->columnSpanFull();
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

        return 'Si cambias la frecuencia, se conservan los meses ya registrados: '.Arr::join($registrados, ', ', ' y ').'.';
    }
}
