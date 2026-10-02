<?php

namespace App\Actions;

use App\Enums\Periodo;
use App\Models\ConvenioEjecucionMensual;
use App\Models\PlanMantenimiento;
use App\Models\PresupuestoMantenimiento;

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
 *
 * RF-67: con un {@see Periodo} de trimestre o semestre, MP ejecutado y MC ejecutado suman solo los
 * meses del período, y MP programado se prorratea según los meses que cubre (un trimestre = 3/12 del
 * costo anual de referencia), porque el costo del plan se registra como monto anual.
 *
 * RF-76 (Módulo 17): el programado sale del gasto programado anual de los recintos
 * (`presupuestos_mantenimiento`, tomado de la cabecera de la planilla), prorrateado por período.
 * MC deja de quedar sin programado cuando hay presupuesto registrado. Para MP, si ningún recinto
 * del alcance tiene presupuesto, se mantiene la suma de `costo_anual_referencia` de los planes.
 * Con filtro por servicio clínico no se usa el presupuesto, porque es del establecimiento completo
 * y no se puede atribuir a un servicio.
 */
class ObtenerDetalleGastoAction
{
    public function __construct(private readonly CalcularGastoCorrectivoAction $calcularGastoCorrectivo) {}

    /**
     * @return array{mp: array{programado: float, ejecutado: float, porcentaje: float|null, fuente: string}, mc: array{programado: float|null, ejecutado: float, porcentaje: float|null}}
     */
    public function execute(int $anio, ?int $recintoId = null, ?int $servicioClinicoId = null, ?Periodo $periodo = null): array
    {
        $planesDelAlcance = fn () => PlanMantenimiento::query()
            ->where('anio', $anio)
            ->when($recintoId !== null || $servicioClinicoId !== null, fn ($query) => $query->whereHas(
                'equipo',
                fn ($equipo) => $equipo->delAlcance($recintoId, $servicioClinicoId)
            ));

        $periodo ??= Periodo::Anual;

        $presupuesto = $this->presupuesto($anio, $recintoId, $servicioClinicoId);
        $mpProgramadoAnual = $presupuesto['mp'] ?? (float) $planesDelAlcance()->sum('costo_anual_referencia');
        $mpProgramado = round($mpProgramadoAnual * $periodo->fraccionDelAnio(), 2);
        $mcProgramado = $presupuesto['mc'] !== null ? round($presupuesto['mc'] * $periodo->fraccionDelAnio(), 2) : null;

        $convenioIds = $planesDelAlcance()
            ->whereNotNull('convenio_id')
            ->pluck('convenio_id')
            ->unique();

        $mpEjecutado = (float) ConvenioEjecucionMensual::whereIn('convenio_id', $convenioIds)
            ->where('anio', $anio)
            ->when(! $periodo->esAnual(), fn ($query) => $query->whereIn('mes', $periodo->meses()))
            ->sum('monto');

        $mcEjecutado = $this->calcularGastoCorrectivo->execute($anio, $recintoId, $servicioClinicoId, $periodo);

        return [
            'mp' => [
                'programado' => $mpProgramado,
                'ejecutado' => $mpEjecutado,
                'porcentaje' => $mpProgramado > 0 ? round($mpEjecutado / $mpProgramado * 100, 1) : null,
                'fuente' => $presupuesto['mp'] !== null ? 'presupuesto' : 'planes',
            ],
            'mc' => [
                'programado' => $mcProgramado,
                'ejecutado' => $mcEjecutado,
                'porcentaje' => $mcProgramado > 0 ? round($mcEjecutado / $mcProgramado * 100, 1) : null,
            ],
        ];
    }

    /**
     * Gasto programado anual de MP y MC de los recintos del alcance (el global scope ya limita a
     * los recintos del usuario, RF-63). `null` si no hay presupuesto registrado o si hay filtro por
     * servicio clínico.
     *
     * @return array{mp: float|null, mc: float|null}
     */
    private function presupuesto(int $anio, ?int $recintoId, ?int $servicioClinicoId): array
    {
        if ($servicioClinicoId !== null) {
            return ['mp' => null, 'mc' => null];
        }

        $presupuestos = PresupuestoMantenimiento::query()
            ->where('anio', $anio)
            ->when($recintoId !== null, fn ($query) => $query->where('recinto_id', $recintoId))
            ->get(['gasto_programado_mp', 'gasto_programado_mc']);

        $suma = fn (string $columna): ?float => $presupuestos->whereNotNull($columna)->isEmpty()
            ? null
            : (float) $presupuestos->sum($columna);

        return ['mp' => $suma('gasto_programado_mp'), 'mc' => $suma('gasto_programado_mc')];
    }
}
