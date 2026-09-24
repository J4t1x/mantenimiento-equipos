<?php

namespace App\Exports;

use App\Models\MantenimientoCorrectivo;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * RF-53 del SRS: exportación del listado de Mantenimiento correctivo con los filtros/búsqueda
 * activos en pantalla (`App\Filament\Support\AccionExportarTablaFiltrada`) — mismas columnas
 * visibles en `MantenimientoCorrectivosTable`, a diferencia del agregado por todo el catastro de
 * RF-33 (`DetalleGastoExport`).
 */
class MantenimientoCorrectivosFiltradosExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        return $this->query->with('equipo');
    }

    public function headings(): array
    {
        return ['Equipo', 'Fecha', 'Falla', 'Costo', 'Tipo de gasto'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($correctivo): array
    {
        /** @var MantenimientoCorrectivo $correctivo */
        return [
            $correctivo->equipo->nombre,
            $correctivo->fecha->format('d-m-Y'),
            $correctivo->falla_descripcion,
            (float) $correctivo->costo,
            $correctivo->tipo_gasto,
        ];
    }

    public function title(): string
    {
        return 'Mantenimiento correctivo';
    }
}
