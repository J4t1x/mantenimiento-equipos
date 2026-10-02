<?php

namespace App\Actions;

use App\Enums\Criticidad;
use App\Enums\EstadoEjecucion;
use App\Enums\Periodo;
use App\Support\AlcanceRecinto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * RN-03 del BRIEF (RF-21/RF-22): % de cumplimiento de mantenimiento preventivo = ejecutados /
 * programados, para un año (y opcionalmente un mes) dado, desagregado en cuatro cortes: total,
 * equipos críticos (EQC), equipos relevantes (EQR) e IM≥12.
 *
 * "Programados" = todo mes que en algún momento tuvo una marca "Programado" (estado programado,
 * realizado o reprogramado — RN-02); "sin_programar" no cuenta, según BRIEF §2: "una celda vacía
 * significa que ese mes no corresponde programación". "Ejecutados" = estado "realizado"; un mes
 * "reprogramado" cuenta como programado pero no como ejecutado.
 *
 * Se calcula en tiempo de lectura (ADR-04 del SAD) vía `DB::table()` (no `EjecucionMensual::query()`)
 * a propósito: así el resultado de `selectRaw` llega como valores planos, sin que el cast a enum de
 * `estado` en el modelo interfiera con las comparaciones de esta agregación.
 *
 * RF-54: `execute()` acepta un filtro opcional por recinto y/o servicio clínico del equipo, para el
 * reporte de cumplimiento (RF-32); el Escritorio sigue llamándolo sin filtro.
 *
 * RF-63: como `DB::table()` no pasa por el global scope de recinto de los modelos, `execute()` y
 * `porMes()` aplican el alcance del usuario con {@see AlcanceRecinto::limitarConsulta()}.
 * `paraEquipo()` no lo necesita: recibe un equipo ya resuelto desde su ficha, que sí está acotada.
 *
 * RF-67: `execute()` acepta además un {@see Periodo} (trimestre o semestre) que acota los meses
 * del año; `null` o `Periodo::Anual` equivalen al año completo.
 */
class CalcularCumplimientoMpAction
{
    private const ESTADOS_PROGRAMADOS = [
        EstadoEjecucion::Programado->value,
        EstadoEjecucion::Realizado->value,
        EstadoEjecucion::Reprogramado->value,
    ];

    /**
     * @return array<string, array{programados: int, ejecutados: int, porcentaje: float|null}>
     */
    public function execute(int $anio, ?int $mes = null, ?int $recintoId = null, ?int $servicioClinicoId = null, ?Periodo $periodo = null): array
    {
        $query = DB::table('ejecuciones_mensuales')
            ->join('planes_mantenimiento', 'planes_mantenimiento.id', '=', 'ejecuciones_mensuales.plan_mantenimiento_id')
            ->join('equipos', 'equipos.id', '=', 'planes_mantenimiento.equipo_id')
            ->where('planes_mantenimiento.anio', $anio)
            ->whereIn('ejecuciones_mensuales.estado', self::ESTADOS_PROGRAMADOS);

        if ($mes !== null) {
            $query->where('ejecuciones_mensuales.mes', $mes);
        }

        if ($periodo !== null && ! $periodo->esAnual()) {
            $query->whereIn('ejecuciones_mensuales.mes', $periodo->meses());
        }

        if ($recintoId !== null) {
            $query->where('equipos.recinto_id', $recintoId);
        }

        if ($servicioClinicoId !== null) {
            $query->where('equipos.servicio_clinico_id', $servicioClinicoId);
        }

        AlcanceRecinto::limitarConsulta($query, 'equipos.recinto_id');

        $filas = $query
            ->selectRaw('equipos.criticidad as criticidad, ejecuciones_mensuales.estado as estado, count(*) as total')
            ->groupBy('equipos.criticidad', 'ejecuciones_mensuales.estado')
            ->get();

        return $this->acumularPorCorte($filas);
    }

    /**
     * RF-58/RF-61 (Módulo 13): corte "total" mes a mes de un año, en una sola consulta agrupada
     * por mes — mismo criterio de programados/ejecutados que `execute()`, para no hacer 12 llamadas
     * a `execute($anio, $mes)` desde los gráficos del Escritorio. Los meses sin nada programado
     * quedan con `porcentaje` en `null`.
     *
     * @return array<int, array{programados: int, ejecutados: int, porcentaje: float|null}>
     */
    public function porMes(int $anio): array
    {
        $query = DB::table('ejecuciones_mensuales')
            ->join('planes_mantenimiento', 'planes_mantenimiento.id', '=', 'ejecuciones_mensuales.plan_mantenimiento_id')
            ->join('equipos', 'equipos.id', '=', 'planes_mantenimiento.equipo_id')
            ->where('planes_mantenimiento.anio', $anio)
            ->whereIn('ejecuciones_mensuales.estado', self::ESTADOS_PROGRAMADOS);

        $filas = AlcanceRecinto::limitarConsulta($query, 'equipos.recinto_id')
            ->selectRaw('ejecuciones_mensuales.mes as mes, ejecuciones_mensuales.estado as estado, count(*) as total')
            ->groupBy('ejecuciones_mensuales.mes', 'ejecuciones_mensuales.estado')
            ->get();

        $meses = [];

        foreach (range(1, 12) as $mes) {
            $delMes = $filas->where('mes', $mes);
            $programados = (int) $delMes->sum('total');
            $ejecutados = (int) $delMes->where('estado', EstadoEjecucion::Realizado->value)->sum('total');

            $meses[$mes] = [
                'programados' => $programados,
                'ejecutados' => $ejecutados,
                'porcentaje' => $programados > 0 ? round($ejecutados / $programados * 100, 1) : null,
            ];
        }

        return $meses;
    }

    /**
     * Cumplimiento de un único equipo (RF-23: "accesible desde el módulo de Equipos").
     *
     * @return array{programados: int, ejecutados: int, porcentaje: float|null}
     */
    public function paraEquipo(int $equipoId, int $anio): array
    {
        $filas = DB::table('ejecuciones_mensuales')
            ->join('planes_mantenimiento', 'planes_mantenimiento.id', '=', 'ejecuciones_mensuales.plan_mantenimiento_id')
            ->where('planes_mantenimiento.equipo_id', $equipoId)
            ->where('planes_mantenimiento.anio', $anio)
            ->whereIn('ejecuciones_mensuales.estado', self::ESTADOS_PROGRAMADOS)
            ->selectRaw('ejecuciones_mensuales.estado as estado, count(*) as total')
            ->groupBy('ejecuciones_mensuales.estado')
            ->get();

        $programados = (int) $filas->sum('total');
        $ejecutados = (int) ($filas->firstWhere('estado', EstadoEjecucion::Realizado->value)?->total ?? 0);

        return [
            'programados' => $programados,
            'ejecutados' => $ejecutados,
            'porcentaje' => $programados > 0 ? round($ejecutados / $programados * 100, 1) : null,
        ];
    }

    /**
     * @param  Collection<int, object{criticidad: string, estado: string, total: int}>  $filas
     * @return array<string, array{programados: int, ejecutados: int, porcentaje: float|null}>
     */
    private function acumularPorCorte(Collection $filas): array
    {
        $cortes = [
            'total' => ['programados' => 0, 'ejecutados' => 0],
            Criticidad::Critico->value => ['programados' => 0, 'ejecutados' => 0],
            Criticidad::Relevante->value => ['programados' => 0, 'ejecutados' => 0],
            Criticidad::ImMayorIgual12->value => ['programados' => 0, 'ejecutados' => 0],
        ];

        foreach ($filas as $fila) {
            $esEjecutado = $fila->estado === EstadoEjecucion::Realizado->value;

            $cortes['total']['programados'] += $fila->total;
            $cortes['total']['ejecutados'] += $esEjecutado ? $fila->total : 0;

            if (array_key_exists($fila->criticidad, $cortes)) {
                $cortes[$fila->criticidad]['programados'] += $fila->total;
                $cortes[$fila->criticidad]['ejecutados'] += $esEjecutado ? $fila->total : 0;
            }
        }

        foreach ($cortes as &$corte) {
            $corte['porcentaje'] = $corte['programados'] > 0
                ? round($corte['ejecutados'] / $corte['programados'] * 100, 1)
                : null;
        }

        return $cortes;
    }
}
