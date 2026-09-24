<?php

namespace App\Exports;

use App\Actions\CalcularEjecucionConvenioAction;
use App\Models\Convenio;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * RF-53 del SRS: exportación del listado de Convenios con los filtros/búsqueda activos en
 * pantalla (`App\Filament\Support\AccionExportarTablaFiltrada`) — mismas columnas visibles en
 * `ConveniosTable`, incluida la ejecución RN-05 del año en curso, a diferencia del reporte anual
 * de RF-33 (`DetalleGastoExport`, agregado por todo el catastro, no por convenio).
 */
class ConveniosFiltradosExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        return $this->query->with('proveedor');
    }

    public function headings(): array
    {
        return [
            'Proveedor', 'Convenio', 'N° resolución', 'Fecha resolución', 'Fecha expiración',
            'Monto anual', 'SIGFE', 'Activo', 'Ejecución '.now()->year.' (%)',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($convenio): array
    {
        /** @var Convenio $convenio */
        $ejecucion = app(CalcularEjecucionConvenioAction::class)->paraConvenio($convenio, now()->year);

        return [
            $convenio->proveedor->nombre,
            $convenio->nombre,
            $convenio->n_resolucion,
            $convenio->fecha_resolucion->format('d-m-Y'),
            $convenio->fecha_expiracion->format('d-m-Y'),
            (float) $convenio->monto_anual,
            $convenio->subasignacion_sigfe,
            $convenio->activo ? 'Sí' : 'No',
            $ejecucion['porcentaje'],
        ];
    }

    public function title(): string
    {
        return 'Convenios';
    }
}
