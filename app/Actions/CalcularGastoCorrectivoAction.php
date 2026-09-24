<?php

namespace App\Actions;

use App\Models\MantenimientoCorrectivo;

/**
 * RF-25 del SRS (RN-04): cada evento de mantenimiento correctivo (MC) registrado suma
 * automáticamente al gasto ejecutado de MC del período correspondiente. Calculado en tiempo de
 * lectura (ADR-04 del SAD, mismo patrón que RN-01/RN-03/RN-05) — sin columna persistida, para que
 * no pueda desincronizarse de los eventos que efectivamente existen.
 */
class CalcularGastoCorrectivoAction
{
    /**
     * Gasto MC ejecutado de todo el catastro en un año (usado por RF-33, `ObtenerDetalleGastoAction`),
     * opcionalmente acotado al recinto y/o servicio clínico del equipo (RF-54).
     */
    public function execute(int $anio, ?int $recintoId = null, ?int $servicioClinicoId = null): float
    {
        return (float) MantenimientoCorrectivo::query()
            ->whereYear('fecha', $anio)
            ->when($recintoId !== null || $servicioClinicoId !== null, fn ($query) => $query->whereHas(
                'equipo',
                fn ($equipo) => $equipo->delAlcance($recintoId, $servicioClinicoId)
            ))
            ->sum('costo');
    }

    /**
     * RF-59 (Módulo 13): gasto MC ejecutado de todo el catastro, mes a mes, para el gráfico de gasto
     * del Escritorio. Se agrupa en PHP (no con `EXTRACT(MONTH ...)`) para no atar la consulta al
     * motor de base de datos; el volumen de correctivos de un año es bajo.
     *
     * @return array<int, float> mes (1-12) => gasto
     */
    public function porMes(int $anio): array
    {
        $porMes = MantenimientoCorrectivo::query()
            ->whereYear('fecha', $anio)
            ->get(['fecha', 'costo'])
            ->groupBy(fn (MantenimientoCorrectivo $correctivo): int => $correctivo->fecha->month)
            ->map(fn ($correctivos): float => (float) $correctivos->sum('costo'));

        return collect(range(1, 12))
            ->mapWithKeys(fn (int $mes): array => [$mes => $porMes[$mes] ?? 0.0])
            ->all();
    }

    /**
     * Gasto MC ejecutado de un único equipo en un año (RF-26: visible en su ficha).
     */
    public function paraEquipo(int $equipoId, int $anio): float
    {
        return (float) MantenimientoCorrectivo::query()
            ->where('equipo_id', $equipoId)
            ->whereYear('fecha', $anio)
            ->sum('costo');
    }
}
