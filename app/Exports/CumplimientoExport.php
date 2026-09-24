<?php

namespace App\Exports;

use App\Actions\CalcularCumplimientoMpAction;
use App\Enums\Criticidad;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * RF-32 del SRS: indicador de cumplimiento de MP por período, en los 4 cortes de RN-03. Reutiliza
 * `CalcularCumplimientoMpAction`, la misma lógica que ya muestra el Escritorio y la ficha de
 * equipo (RF-21/RF-22/RF-23) — no se recalcula distinto para el reporte. Filtro opcional de
 * recinto/servicio clínico (RF-54).
 */
class CumplimientoExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly int $anio,
        private readonly ?int $recintoId = null,
        private readonly ?int $servicioClinicoId = null,
    ) {}

    public function array(): array
    {
        $cortes = app(CalcularCumplimientoMpAction::class)->execute($this->anio, recintoId: $this->recintoId, servicioClinicoId: $this->servicioClinicoId);

        $etiquetas = [
            'total' => 'Total',
            Criticidad::Critico->value => 'Equipos críticos (EQC)',
            Criticidad::Relevante->value => 'Equipos relevantes (EQR)',
            Criticidad::ImMayorIgual12->value => 'IM ≥ 12',
        ];

        return collect($etiquetas)
            ->map(fn (string $etiqueta, string $clave): array => [
                $etiqueta,
                $cortes[$clave]['programados'],
                $cortes[$clave]['ejecutados'],
                $cortes[$clave]['porcentaje'] !== null ? "{$cortes[$clave]['porcentaje']}%" : 'Sin datos',
            ])
            ->values()
            ->all();
    }

    public function headings(): array
    {
        return ['Corte', 'Programados', 'Ejecutados', '% cumplimiento'];
    }

    public function title(): string
    {
        return "Cumplimiento MP {$this->anio}";
    }
}
