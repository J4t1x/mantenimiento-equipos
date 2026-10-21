<?php

namespace App\Exports;

use App\Actions\ObtenerConveniosEjecucionAction;
use App\Enums\Periodo;
use App\Filament\Support\BitacoraMensual;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * RF-82 (Módulo 19): convenios y su ejecución en dos hojas: una fila por convenio con su monto
 * anual, lo ejecutado y el % del año, y el detalle mensual por orden de compra del período. Los
 * datos vienen de {@see ObtenerConveniosEjecucionAction}.
 */
class ConveniosEjecucionExport implements Export, WithMultipleSheets
{
    public function __construct(
        private readonly int $anio,
        private readonly Periodo $periodo = Periodo::Anual,
    ) {}

    /**
     * @return array<int, HojaExport>
     */
    public function sheets(): array
    {
        $datos = app(ObtenerConveniosEjecucionAction::class)->execute($this->anio, $this->periodo);
        $etiquetaPeriodo = $this->periodo->esAnual() ? (string) $this->anio : $this->periodo->getLabel().' '.$this->anio;

        return [
            new HojaExport(
                'Resumen',
                ['Proveedor', 'Convenio', 'N° resolución', 'Fecha de expiración', 'Activo', 'Monto anual', "Ejecutado {$this->anio}", "Ejecutado {$etiquetaPeriodo}", "% del monto anual ({$this->anio})", 'Sobregirado'],
                $datos['convenios']->map(fn (array $fila): array => [
                    $fila['proveedor'], $fila['convenio'], $fila['n_resolucion'], $fila['fecha_expiracion'] ?? '—', $fila['activo'] ? 'Sí' : 'No',
                    $fila['monto_anual'], $fila['ejecutado_anio'], $fila['ejecutado_periodo'],
                    $fila['porcentaje'] !== null ? "{$fila['porcentaje']}%" : 'Sin monto', $fila['sobregirado'] ? 'Sí' : 'No',
                ])->all(),
            ),

            new HojaExport(
                'Detalle mensual',
                ['Proveedor', 'Convenio', 'Mes', 'N° orden de compra / factura', 'Monto'],
                $datos['detalle']->map(fn (array $fila): array => [
                    $fila['proveedor'], $fila['convenio'], BitacoraMensual::MESES[$fila['mes']], $fila['n_orden_compra'], $fila['monto'],
                ])->all(),
            ),
        ];
    }
}
