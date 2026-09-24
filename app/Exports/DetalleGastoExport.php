<?php

namespace App\Exports;

use App\Actions\ObtenerDetalleGastoAction;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * RF-33 del SRS. Ver `App\Actions\ObtenerDetalleGastoAction` para la lógica de datos y la
 * limitación de MC sin "programado" (pregunta abierta 6 del BRIEF). Filtro opcional de
 * recinto/servicio clínico (RF-54).
 */
class DetalleGastoExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly int $anio,
        private readonly ?int $recintoId = null,
        private readonly ?int $servicioClinicoId = null,
    ) {}

    public function array(): array
    {
        $detalle = app(ObtenerDetalleGastoAction::class)->execute($this->anio, $this->recintoId, $this->servicioClinicoId);

        return [
            ['Mantenimiento preventivo (MP)', $detalle['mp']['programado'], $detalle['mp']['ejecutado'], $detalle['mp']['porcentaje'] !== null ? "{$detalle['mp']['porcentaje']}%" : 'Sin datos'],
            ['Mantenimiento correctivo (MC)', $detalle['mc']['programado'] ?? 'Sin dato (no definido, ver pregunta abierta 6)', $detalle['mc']['ejecutado'], 'N/A'],
        ];
    }

    public function headings(): array
    {
        return ['Tipo', 'Gasto programado', 'Gasto ejecutado', '% ejecución'];
    }

    public function title(): string
    {
        return "Detalle de gasto {$this->anio}";
    }
}
