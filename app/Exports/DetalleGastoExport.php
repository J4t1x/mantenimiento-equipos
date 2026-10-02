<?php

namespace App\Exports;

use App\Actions\ObtenerDetalleGastoAction;
use App\Enums\Periodo;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * RF-33 del SRS. Ver `App\Actions\ObtenerDetalleGastoAction` para la lógica de datos. Desde RF-76,
 * el programado (MP y MC) sale del gasto programado anual del recinto cuando está registrado. Filtro opcional de
 * recinto/servicio clínico (RF-54) y período de trimestre o semestre (RF-67).
 */
class DetalleGastoExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly int $anio,
        private readonly ?int $recintoId = null,
        private readonly ?int $servicioClinicoId = null,
        private readonly ?Periodo $periodo = null,
    ) {}

    public function array(): array
    {
        $detalle = app(ObtenerDetalleGastoAction::class)->execute($this->anio, $this->recintoId, $this->servicioClinicoId, $this->periodo);

        return [
            ['Mantenimiento preventivo (MP)', $detalle['mp']['programado'], $detalle['mp']['ejecutado'], $detalle['mp']['porcentaje'] !== null ? "{$detalle['mp']['porcentaje']}%" : 'Sin datos'],
            ['Mantenimiento correctivo (MC)', $detalle['mc']['programado'] ?? 'Sin gasto programado registrado', $detalle['mc']['ejecutado'], $detalle['mc']['porcentaje'] !== null ? "{$detalle['mc']['porcentaje']}%" : 'Sin datos'],
        ];
    }

    public function headings(): array
    {
        return ['Tipo', 'Gasto programado', 'Gasto ejecutado', '% ejecución'];
    }

    public function title(): string
    {
        return trim("Detalle de gasto {$this->anio} {$this->periodo?->codigo()}");
    }
}
