<?php

namespace App\Exports;

use App\Actions\ObtenerFilasCatastroPlanAction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * RF-31 del SRS (filtro opcional de recinto/servicio clínico, RF-54). Ver `App\Actions\ObtenerFilasCatastroPlanAction` para la lógica de datos.
 */
class CatastroPlanExport implements FromCollection, WithHeadings, WithTitle
{
    /** @var Collection<int, array<string, mixed>> */
    private readonly Collection $filas;

    private const ENCABEZADOS_SIN_FILAS = [
        'Recinto', 'Servicio clínico', 'Equipo', 'N° inventario', 'Criticidad', 'Frecuencia anual',
        'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic',
    ];

    public function __construct(int $anio, ?int $recintoId = null, ?int $servicioClinicoId = null)
    {
        $this->filas = app(ObtenerFilasCatastroPlanAction::class)->execute($anio, $recintoId, $servicioClinicoId);
    }

    public function collection(): Collection
    {
        return $this->filas->map(fn (array $fila): array => array_values($fila));
    }

    public function headings(): array
    {
        return array_keys($this->filas->first() ?? array_fill_keys(self::ENCABEZADOS_SIN_FILAS, null));
    }

    public function title(): string
    {
        return 'Catastro y plan';
    }
}
