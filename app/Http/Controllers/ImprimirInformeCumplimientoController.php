<?php

namespace App\Http\Controllers;

use App\Actions\CalcularCumplimientoMpAction;
use App\Actions\ObtenerDetalleGastoAction;
use App\Actions\ObtenerInformeCriticosAction;
use App\Enums\Periodo;
use App\Models\Recinto;
use App\Models\ServicioClinico;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * RF-78 (Módulo 18): informe imprimible de cumplimiento de MP y gasto de un período, para los
 * informes trimestrales y de fin de año que pide el requirente (respuesta 8 del BRIEF). Completa
 * el "PDF" que RF-32 contemplaba. Reúne en un documento el indicador de cumplimiento en sus 4 cortes
 * (RN-03), el detalle de gasto MP y MC con su programado (RF-76) y el indicador de equipos
 * críticos de la norma (RF-68), con los mismos datos que Reportes. Mismo permiso y mismo registro
 * de ruta que el informe de críticos imprimible (RF-77).
 */
class ImprimirInformeCumplimientoController extends Controller
{
    public function __invoke(
        Request $request,
        CalcularCumplimientoMpAction $calcularCumplimiento,
        ObtenerDetalleGastoAction $obtenerGasto,
        ObtenerInformeCriticosAction $obtenerInformeCriticos,
    ): View {
        abort_unless((bool) $request->user()?->can('view.reportes'), 403);

        $filtros = $request->validate([
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'periodo' => ['nullable', Rule::enum(Periodo::class)],
            'recinto_id' => ['nullable', 'integer'],
            'servicio_clinico_id' => ['nullable', 'integer'],
        ]);

        $anio = (int) $filtros['anio'];
        $periodo = Periodo::tryFrom((string) ($filtros['periodo'] ?? '')) ?? Periodo::Anual;
        $recinto = filled($filtros['recinto_id'] ?? null) ? Recinto::query()->findOrFail($filtros['recinto_id']) : null;
        $servicioClinico = filled($filtros['servicio_clinico_id'] ?? null) ? ServicioClinico::query()->findOrFail($filtros['servicio_clinico_id']) : null;
        $informeCriticos = $obtenerInformeCriticos->execute($anio, $periodo, $recinto?->id, $servicioClinico?->id);

        return view('informes.informe-cumplimiento', [
            'anio' => $anio,
            'periodo' => $periodo,
            'recinto' => $recinto,
            'servicioClinico' => $servicioClinico,
            'cortes' => $calcularCumplimiento->execute($anio, recintoId: $recinto?->id, servicioClinicoId: $servicioClinico?->id, periodo: $periodo),
            'gasto' => $obtenerGasto->execute($anio, $recinto?->id, $servicioClinico?->id, $periodo),
            'criticos' => $informeCriticos['resumen'],
            'responsablesMp' => $informeCriticos['responsables_mp'],
            'emitidoPor' => $request->user()?->name,
        ]);
    }
}
