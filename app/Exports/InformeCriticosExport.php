<?php

namespace App\Exports;

use App\Actions\ObtenerInformeCriticosAction;
use App\Enums\Periodo;
use App\Models\Recinto;
use App\Models\ServicioClinico;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * RF-68 del SRS: informe de cumplimiento de equipos críticos de la Res. Ex. 1341/2017 del MINSAL
 * (§8), en tres hojas: el indicador de la norma, el detalle por equipo crítico y las
 * reprogramaciones del período con sus causas. Los datos vienen de
 * {@see ObtenerInformeCriticosAction}.
 */
class InformeCriticosExport implements Export, WithMultipleSheets
{
    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function __construct(
        private readonly int $anio,
        private readonly ?int $recintoId = null,
        private readonly ?int $servicioClinicoId = null,
        private readonly Periodo $periodo = Periodo::Anual,
    ) {}

    /**
     * @return array<int, HojaExport>
     */
    public function sheets(): array
    {
        $informe = app(ObtenerInformeCriticosAction::class)->execute($this->anio, $this->periodo, $this->recintoId, $this->servicioClinicoId);
        $resumen = $informe['resumen'];

        return [
            new HojaExport('Resumen', ['Concepto', 'Valor'], [
                ['Período', $this->periodo->getLabel().' '.$this->anio],
                ['Recinto', $this->recintoId !== null ? Recinto::find($this->recintoId)?->nombre : 'Todos'],
                ['Responsable de MP', $informe['responsables_mp']],
                ["Programa anual {$this->anio}", $informe['programa_anual']],
                ['Servicio clínico', $this->servicioClinicoId !== null ? ServicioClinico::find($this->servicioClinicoId)?->nombre : 'Todos'],
                ['Equipos críticos existentes (activos)', $resumen['equipos_criticos']],
                ["Equipos críticos activos sin plan de MP {$this->anio}", $resumen['criticos_sin_plan']],
                ['Equipos críticos con MP programada en el período', $resumen['con_mp_programada']],
                ['Equipos críticos con MP ejecutada en el período', $resumen['con_mp_ejecutada']],
                ['% de cumplimiento (ejecutados / programados × 100)', $resumen['porcentaje'] !== null ? "{$resumen['porcentaje']}%" : 'Sin datos'],
                ['Mantenciones programadas', $resumen['mantenciones_programadas']],
                ['Mantenciones realizadas', $resumen['mantenciones_realizadas']],
                ['Reprogramaciones del período', $resumen['reprogramaciones']],
                ...collect($informe['tipos_norma'])->map(fn (int $cantidad, string $tipo): array => ["Críticos activos por tipo de la norma: {$tipo}", $cantidad])->values()->all(),
                ['Criterio', 'Un equipo cuenta como ejecutado si se realizaron todas sus MP programadas del período. Norma: Res. Ex. 1341/2017 del MINSAL.'],
            ]),

            new HojaExport(
                'Equipos críticos',
                ['Recinto', 'Servicio clínico', 'Equipo', 'Marca', 'Modelo', 'N° de inventario', 'Frecuencia anual', 'Periodicidad', 'MP programadas', 'MP realizadas', 'Reprogramadas pendientes', 'Cumple'],
                $informe['equipos']->map(fn (array $fila): array => [
                    $fila['recinto'], $fila['servicio_clinico'], $fila['equipo'], $fila['marca'], $fila['modelo'], $fila['n_inventario'],
                    $fila['frecuencia_anual'], $fila['periodicidad_fabricante'] ? 'De fabricante (garantía)' : 'Norma (mínimo 2)',
                    $fila['programadas'], $fila['realizadas'], $fila['reprogramadas_pendientes'], $fila['cumple'] ? 'Sí' : 'No',
                ])->all(),
            ),

            new HojaExport(
                'Reprogramaciones',
                ['Recinto', 'Equipo', 'N° de inventario', 'Mes programado', 'Causa', 'Documento de justificación', 'Plazo (30 días)', 'Estado actual', 'Plazo vencido', 'Retiro de uso'],
                $informe['reprogramaciones']->map(fn (array $fila): array => [
                    $fila['recinto'], $fila['equipo'], $fila['n_inventario'], self::MESES[$fila['mes']], $fila['causa'], $fila['documento'] ?? '—',
                    $fila['plazo'], $fila['estado']->getLabel(), $fila['vencida'] ? 'Sí' : 'No', $fila['retiro_uso'] ?? '—',
                ])->all(),
            ),
        ];
    }
}
