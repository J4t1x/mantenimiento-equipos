<?php

namespace App\Actions;

use App\Models\ConvenioEjecucionMensual;
use App\Models\PlanMantenimiento;

/**
 * RF-33 del SRS: detalle de gasto de mantenimiento preventivo (MP) versus correctivo (MC),
 * programado versus ejecutado, para un año dado (RN-04).
 *
 * MP programado = suma de `costo_anual_referencia` de los planes del año. MP ejecutado = suma de
 * las ejecuciones mensuales de los convenios asociados a esos planes, en el mismo año — es una
 * aproximación a nivel de convenio, no de plan individual, porque un convenio puede cubrir más de
 * un equipo. MC no tiene un "programado" en el modelo actual: es mantenimiento reactivo y la
 * planilla original solo trae el gasto agregado (pregunta abierta 6 del BRIEF, sin resolver) — se
 * deja en `null` en vez de inventar un valor. MC ejecutado delega en `CalcularGastoCorrectivoAction`
 * (RF-25) para no duplicar el criterio de suma en dos lugares.
 *
 * RF-54: filtro opcional por recinto y/o servicio clínico del equipo. Con filtro, MP ejecutado sigue
 * siendo la aproximación a nivel de convenio descrita arriba: suma el gasto completo de los convenios
 * que cubren al menos un plan dentro del alcance, aunque ese convenio cubra también equipos de otros
 * recintos o servicios — el modelo no registra a qué equipo corresponde cada monto mensual.
 */
class ObtenerDetalleGastoAction
{
    public function __construct(private readonly CalcularGastoCorrectivoAction $calcularGastoCorrectivo) {}

    /**
     * @return array{mp: array{programado: float, ejecutado: float, porcentaje: float|null}, mc: array{programado: null, ejecutado: float, porcentaje: null}}
     */
    public function execute(int $anio, ?int $recintoId = null, ?int $servicioClinicoId = null): array
    {
        $planesDelAlcance = fn () => PlanMantenimiento::query()
            ->where('anio', $anio)
            ->when($recintoId !== null || $servicioClinicoId !== null, fn ($query) => $query->whereHas(
                'equipo',
                fn ($equipo) => $equipo->delAlcance($recintoId, $servicioClinicoId)
            ));

        $mpProgramado = (float) $planesDelAlcance()->sum('costo_anual_referencia');

        $convenioIds = $planesDelAlcance()
            ->whereNotNull('convenio_id')
            ->pluck('convenio_id')
            ->unique();

        $mpEjecutado = (float) ConvenioEjecucionMensual::whereIn('convenio_id', $convenioIds)
            ->where('anio', $anio)
            ->sum('monto');

        $mcEjecutado = $this->calcularGastoCorrectivo->execute($anio, $recintoId, $servicioClinicoId);

        return [
            'mp' => [
                'programado' => $mpProgramado,
                'ejecutado' => $mpEjecutado,
                'porcentaje' => $mpProgramado > 0 ? round($mpEjecutado / $mpProgramado * 100, 1) : null,
            ],
            'mc' => [
                'programado' => null,
                'ejecutado' => $mcEjecutado,
                'porcentaje' => null,
            ],
        ];
    }
}
