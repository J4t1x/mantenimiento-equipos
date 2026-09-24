<?php

namespace App\Actions;

use App\Enums\EstadoEjecucion;
use App\Models\PlanMantenimiento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * RF-31 del SRS: reporte de catastro + plan de MP de un año dado, en formato equivalente al de la
 * planilla vigente (mismas columnas y marcas mensuales). Los símbolos por mes son los de
 * `BRIEF.md` §2: `X` Programado, `√` Realizado, `ꓣ` Reprogramado; un mes sin marca queda en blanco.
 *
 * RF-54: acepta un filtro opcional por recinto y/o servicio clínico del equipo del plan.
 */
class ObtenerFilasCatastroPlanAction
{
    private const SIMBOLOS = [
        EstadoEjecucion::Programado->value => 'X',
        EstadoEjecucion::Realizado->value => '√',
        EstadoEjecucion::Reprogramado->value => 'ꓣ',
    ];

    private const MESES = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    ];

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function execute(int $anio, ?int $recintoId = null, ?int $servicioClinicoId = null): Collection
    {
        return $this->planesDelAlcance($anio, $recintoId, $servicioClinicoId)
            ->with(['equipo.recinto', 'equipo.servicioClinico', 'ejecucionesMensuales'])
            ->get()
            ->map(function (PlanMantenimiento $plan): array {
                $marcasPorMes = $plan->ejecucionesMensuales
                    ->pluck('estado', 'mes')
                    ->map(fn (EstadoEjecucion $estado): string => self::SIMBOLOS[$estado->value] ?? '');

                $meses = collect(self::MESES)
                    ->mapWithKeys(fn (string $etiqueta, int $mes): array => [$etiqueta => $marcasPorMes->get($mes, '')]);

                return [
                    'Recinto' => $plan->equipo->recinto->nombre,
                    'Servicio clínico' => $plan->equipo->servicioClinico->nombre,
                    'Equipo' => $plan->equipo->nombre,
                    'N° inventario' => $plan->equipo->n_inventario,
                    'Criticidad' => $plan->equipo->criticidad->getLabel(),
                    'Frecuencia anual' => $plan->frecuencia_anual->value,
                    ...$meses->all(),
                ];
            });
    }

    /**
     * RF-62 (Módulo 14): cantidad de filas que tendría el reporte (un plan por equipo y año), para el
     * resumen de la página Reportes — un `count()` directo, sin cargar planes ni ejecuciones.
     */
    public function contar(int $anio, ?int $recintoId = null, ?int $servicioClinicoId = null): int
    {
        return $this->planesDelAlcance($anio, $recintoId, $servicioClinicoId)->count();
    }

    /**
     * @return Builder<PlanMantenimiento>
     */
    private function planesDelAlcance(int $anio, ?int $recintoId, ?int $servicioClinicoId): Builder
    {
        return PlanMantenimiento::query()
            ->where('anio', $anio)
            ->when($recintoId !== null || $servicioClinicoId !== null, fn ($query) => $query->whereHas(
                'equipo',
                fn ($equipo) => $equipo->delAlcance($recintoId, $servicioClinicoId)
            ));
    }
}
