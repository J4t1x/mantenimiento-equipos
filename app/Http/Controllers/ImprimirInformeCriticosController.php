<?php

namespace App\Http\Controllers;

use App\Actions\ObtenerInformeCriticosAction;
use App\Enums\Periodo;
use App\Models\Recinto;
use App\Models\ServicioClinico;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * RF-77 (Módulo 17): versión imprimible del informe de cumplimiento de equipos críticos (RF-68),
 * lista para guardar como PDF desde el navegador, firmar y enviar a la Unidad de Calidad y a la
 * Dirección (Res. Ex. 1341/2017 §8). Usa los mismos datos que el Excel
 * (`ObtenerInformeCriticosAction`) y el mismo permiso que la página Reportes. Se registra como ruta
 * autenticada del panel, así que el alcance por recinto del usuario (RF-63) se aplica solo.
 */
class ImprimirInformeCriticosController extends Controller
{
    public function __invoke(Request $request, ObtenerInformeCriticosAction $obtenerInforme): View
    {
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

        return view('informes.informe-criticos', [
            'anio' => $anio,
            'periodo' => $periodo,
            'recinto' => $recinto,
            'servicioClinico' => $servicioClinico,
            'informe' => $obtenerInforme->execute($anio, $periodo, $recinto?->id, $servicioClinico?->id),
            'emitidoPor' => $request->user()?->name,
        ]);
    }
}
