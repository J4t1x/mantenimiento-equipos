<?php

namespace App\Actions;

use App\Models\Convenio;

/**
 * RN-05 del BRIEF (RF-29/RF-30): un convenio de mantenimiento tiene un monto anual que se imputa
 * mes a mes contra órdenes de compra/facturas; la suma de los montos ejecutados en un año debe
 * conciliar con el monto anual del convenio. Si la supera, el sistema debe **alertar, sin
 * bloquear** (RF-29) — el registro de la ejecución mensual siempre se permite.
 *
 * Se calcula en tiempo de lectura vía accessor de Eloquent (ADR-04 del SAD), no como columna
 * almacenada, para que el porcentaje siempre refleje el estado actual de las ejecuciones.
 */
class CalcularEjecucionConvenioAction
{
    /**
     * @return array{monto_anual: float, monto_ejecutado: float, porcentaje: float|null, sobregirado: bool}
     */
    public function paraConvenio(Convenio $convenio, int $anio): array
    {
        $montoAnual = (float) $convenio->monto_anual;

        $montoEjecutado = (float) $convenio->ejecucionesMensuales()
            ->where('anio', $anio)
            ->sum('monto');

        return [
            'monto_anual' => $montoAnual,
            'monto_ejecutado' => $montoEjecutado,
            'porcentaje' => $montoAnual > 0 ? round($montoEjecutado / $montoAnual * 100, 1) : null,
            'sobregirado' => $montoAnual > 0 && $montoEjecutado > $montoAnual,
        ];
    }

    /**
     * RF-43 (Módulo 11): cantidad de convenios sobregirados en un año — mismo criterio que
     * {@see self::paraConvenio()}, agregado para el widget de alertas del Escritorio en vez de
     * duplicar la regla de sobregiro.
     */
    public function contarSobregirados(int $anio): int
    {
        return Convenio::all()
            ->filter(fn (Convenio $convenio): bool => $this->paraConvenio($convenio, $anio)['sobregirado'])
            ->count();
    }

    /**
     * RF-43: convenios cuya `fecha_expiracion` cae dentro de los próximos `$dias` días (no
     * incluye los ya vencidos).
     */
    public function contarPorVencer(int $dias): int
    {
        return Convenio::query()
            ->whereBetween('fecha_expiracion', [now(), now()->addDays($dias)])
            ->count();
    }
}
