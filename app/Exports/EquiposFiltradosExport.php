<?php

namespace App\Exports;

use App\Models\Equipo;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * RF-53 del SRS: exportación del listado de Equipos con los filtros/búsqueda activos en pantalla
 * (`App\Filament\Support\AccionExportarTablaFiltrada`) — mismas columnas visibles en
 * `EquiposTable`, no el reporte anual completo de RF-31 (`CatastroPlanExport`).
 */
class EquiposFiltradosExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        return $this->query->with(['recinto', 'servicioClinico', 'clase', 'subclase']);
    }

    public function headings(): array
    {
        return [
            'Recinto', 'Servicio clínico', 'Clase', 'Subclase', 'Nombre', 'Marca', 'Modelo',
            'N° de serie', 'N° de inventario', 'Año adquisición', 'Vida útil (años)', 'Propiedad',
            'Estado', 'Criticidad', 'En garantía', 'Vence garantía', 'Activo',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($equipo): array
    {
        /** @var Equipo $equipo */
        return [
            $equipo->recinto->nombre,
            $equipo->servicioClinico->nombre,
            $equipo->clase->nombre,
            $equipo->subclase?->nombre,
            $equipo->nombre,
            $equipo->marca,
            $equipo->modelo,
            $equipo->serie,
            $equipo->n_inventario,
            $equipo->anio_adquisicion,
            $equipo->vida_util_anios,
            $equipo->propiedad->getLabel(),
            $equipo->estado->getLabel(),
            $equipo->criticidad->getLabel(),
            $equipo->en_garantia ? 'Sí' : 'No',
            $equipo->garantia_anio_vencimiento,
            $equipo->activo ? 'Sí' : 'No',
        ];
    }

    public function title(): string
    {
        return 'Equipos';
    }
}
