<?php

namespace App\Actions;

use App\Enums\Periodo;
use App\Models\Convenio;
use App\Models\ConvenioEjecucionMensual;
use Illuminate\Support\Collection;

/**
 * RF-82 (Módulo 19): convenios y su ejecución para la tarjeta "Convenios y ejecución" de Reportes y
 * su Excel. Incluye los convenios activos y los inactivos que tuvieron ejecución en el año.
 *
 * El monto del convenio es anual: el % y el sobregiro (RN-05) se calculan siempre con la ejecución
 * del año completo, con el mismo criterio que {@see CalcularEjecucionConvenioAction}. El período
 * acota la columna "ejecutado en el período" y el detalle mensual. Los convenios no tienen recinto,
 * así que no se acotan por recinto ni servicio clínico. Las sumas se hacen en una sola consulta
 * agregada, no una por convenio.
 */
class ObtenerConveniosEjecucionAction
{
    /**
     * @return array{
     *     resumen: array{convenios_activos: int, monto_comprometido: float, ejecutado_anio: float, ejecutado_periodo: float, porcentaje: float|null, sobregirados: int},
     *     convenios: Collection<int, array{proveedor: ?string, convenio: string, n_resolucion: ?string, fecha_expiracion: ?string, activo: bool, monto_anual: float, ejecutado_anio: float, ejecutado_periodo: float, porcentaje: float|null, sobregirado: bool}>,
     *     detalle: Collection<int, array{proveedor: ?string, convenio: string, mes: int, n_orden_compra: ?string, monto: float}>
     * }
     */
    public function execute(int $anio, Periodo $periodo = Periodo::Anual): array
    {
        $sumas = ConvenioEjecucionMensual::query()
            ->where('anio', $anio)
            ->groupBy('convenio_id')
            ->selectRaw('convenio_id, sum(monto) as ejecutado_anio')
            ->selectRaw('sum(case when mes in ('.implode(',', $periodo->meses()).') then monto else 0 end) as ejecutado_periodo')
            ->get()
            ->keyBy('convenio_id');

        $convenios = Convenio::query()
            ->with('proveedor')
            ->where(fn ($query) => $query->where('activo', true)->orWhereIn('id', $sumas->keys()))
            ->orderBy('nombre')
            ->get()
            ->map(function (Convenio $convenio) use ($sumas): array {
                $montoAnual = (float) $convenio->monto_anual;
                $ejecutadoAnio = (float) ($sumas->get($convenio->id)?->ejecutado_anio ?? 0);

                return [
                    'proveedor' => $convenio->proveedor?->nombre,
                    'convenio' => $convenio->nombre,
                    'n_resolucion' => $convenio->n_resolucion,
                    'fecha_expiracion' => $convenio->fecha_expiracion?->format('d-m-Y'),
                    'activo' => (bool) $convenio->activo,
                    'monto_anual' => $montoAnual,
                    'ejecutado_anio' => $ejecutadoAnio,
                    'ejecutado_periodo' => (float) ($sumas->get($convenio->id)?->ejecutado_periodo ?? 0),
                    'porcentaje' => $montoAnual > 0 ? round($ejecutadoAnio / $montoAnual * 100, 1) : null,
                    'sobregirado' => $montoAnual > 0 && $ejecutadoAnio > $montoAnual,
                ];
            });

        $activos = $convenios->where('activo', true);
        $comprometido = (float) $activos->sum('monto_anual');
        $ejecutadoAnioActivos = (float) $activos->sum('ejecutado_anio');

        $detalle = ConvenioEjecucionMensual::query()
            ->with('convenio.proveedor')
            ->where('anio', $anio)
            ->whereIn('mes', $periodo->meses())
            ->orderBy('mes')
            ->get()
            ->sortBy(fn (ConvenioEjecucionMensual $ejecucion): string => $ejecucion->convenio?->nombre.sprintf('%02d', $ejecucion->mes))
            ->values()
            ->map(fn (ConvenioEjecucionMensual $ejecucion): array => [
                'proveedor' => $ejecucion->convenio?->proveedor?->nombre,
                'convenio' => (string) $ejecucion->convenio?->nombre,
                'mes' => (int) $ejecucion->mes,
                'n_orden_compra' => $ejecucion->n_orden_compra,
                'monto' => (float) $ejecucion->monto,
            ]);

        return [
            'resumen' => [
                'convenios_activos' => $activos->count(),
                'monto_comprometido' => $comprometido,
                'ejecutado_anio' => $ejecutadoAnioActivos,
                'ejecutado_periodo' => (float) $activos->sum('ejecutado_periodo'),
                'porcentaje' => $comprometido > 0 ? round($ejecutadoAnioActivos / $comprometido * 100, 1) : null,
                'sobregirados' => $convenios->where('sobregirado', true)->count(),
            ],
            'convenios' => $convenios,
            'detalle' => $detalle,
        ];
    }
}
