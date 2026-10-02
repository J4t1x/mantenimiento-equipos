<?php

namespace App\Actions;

use App\Enums\Criticidad;
use App\Enums\EstadoEjecucion;
use App\Enums\Periodo;
use App\Enums\TipoEquipoCritico;
use App\Models\EjecucionMensual;
use App\Models\Equipo;
use App\Models\Recinto;
use App\Models\RetiroUso;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * RF-68 del SRS: informe de cumplimiento de equipos críticos de la Res. Ex. 1341/2017 del MINSAL
 * (§8), por período (la norma pide 1er semestre y año completo) y, opcionalmente, recinto y
 * servicio clínico.
 *
 * Indicador de la norma: N° de equipos críticos con MP ejecutada en el período / N° de equipos
 * críticos con MP programada en el período × 100. Cuenta **equipos**, no mantenciones (a
 * diferencia de RN-03). Un equipo cuenta como "con MP ejecutada" solo si se realizaron **todas**
 * sus mantenciones programadas del período: criterio conservador, que solo se distingue del
 * alternativo ("al menos una") en equipos con más de una MP en el período.
 *
 * Reprogramaciones del período: todo mes del período con causa de reprogramación registrada
 * (`causa_reprogramacion`), esté todavía reprogramado o ya realizado.
 *
 * Mismo criterio de "programado" que `CalcularCumplimientoMpAction`: meses en estado programado,
 * realizado o reprogramado. El alcance por recinto del usuario (RF-63) se aplica solo, vía los
 * global scopes de los modelos.
 */
class ObtenerInformeCriticosAction
{
    private const ESTADOS_PROGRAMADOS = [
        EstadoEjecucion::Programado,
        EstadoEjecucion::Realizado,
        EstadoEjecucion::Reprogramado,
    ];

    /**
     * @return array{
     *     resumen: array{equipos_criticos: int, criticos_sin_plan: int, con_mp_programada: int, con_mp_ejecutada: int, porcentaje: float|null, mantenciones_programadas: int, mantenciones_realizadas: int, reprogramaciones: int},
     *     equipos: Collection<int, array{recinto: string, servicio_clinico: string, equipo: string, marca: ?string, modelo: ?string, n_inventario: string, frecuencia_anual: int, periodicidad_fabricante: bool, programadas: int, realizadas: int, reprogramadas_pendientes: int, cumple: bool}>,
     *     responsables_mp: string,
     *     programa_anual: string,
     *     tipos_norma: array<string, int>,
     *     reprogramaciones: Collection<int, array{recinto: string, equipo: string, n_inventario: string, mes: int, causa: string, documento: ?string, plazo: string, estado: EstadoEjecucion, vencida: bool, retiro_uso: ?string}>
     * }
     */
    public function execute(int $anio, Periodo $periodo, ?int $recintoId = null, ?int $servicioClinicoId = null): array
    {
        $ejecuciones = EjecucionMensual::query()
            ->whereIn('mes', $periodo->meses())
            ->whereIn('estado', self::ESTADOS_PROGRAMADOS)
            ->whereHas('planMantenimiento', fn (Builder $plan) => $plan
                ->where('anio', $anio)
                ->whereHas('equipo', fn (Builder $equipo) => $equipo
                    ->where('criticidad', Criticidad::Critico)
                    ->delAlcance($recintoId, $servicioClinicoId)))
            ->with(['planMantenimiento.equipo.recinto', 'planMantenimiento.equipo.servicioClinico', 'planMantenimiento.equipo.retirosUso'])
            ->orderBy('mes')
            ->get();

        $equipos = $ejecuciones
            ->groupBy(fn (EjecucionMensual $ejecucion): int => $ejecucion->planMantenimiento->equipo_id)
            ->map(fn (Collection $delEquipo): array => $this->filaEquipo($delEquipo))
            ->sortBy([['recinto', 'asc'], ['servicio_clinico', 'asc'], ['equipo', 'asc']])
            ->values();

        $reprogramaciones = $ejecuciones
            ->filter(fn (EjecucionMensual $ejecucion): bool => filled($ejecucion->causa_reprogramacion))
            ->map(fn (EjecucionMensual $ejecucion): array => $this->filaReprogramacion($ejecucion, $anio))
            ->values();

        $conMpProgramada = $equipos->count();
        $conMpEjecutada = $equipos->where('cumple', true)->count();

        return [
            'resumen' => [
                'equipos_criticos' => Equipo::query()
                    ->where('criticidad', Criticidad::Critico)
                    ->where('activo', true)
                    ->delAlcance($recintoId, $servicioClinicoId)
                    ->count(),
                // RF-71 (§7.2): críticos activos fuera del programa del año.
                'criticos_sin_plan' => Equipo::query()
                    ->criticosSinPlan($anio)
                    ->delAlcance($recintoId, $servicioClinicoId)
                    ->count(),
                'con_mp_programada' => $conMpProgramada,
                'con_mp_ejecutada' => $conMpEjecutada,
                'porcentaje' => $conMpProgramada > 0 ? round($conMpEjecutada / $conMpProgramada * 100, 1) : null,
                'mantenciones_programadas' => $ejecuciones->count(),
                'mantenciones_realizadas' => $ejecuciones->where('estado', EstadoEjecucion::Realizado)->count(),
                'reprogramaciones' => $reprogramaciones->count(),
            ],
            'equipos' => $equipos,
            'reprogramaciones' => $reprogramaciones,
            'responsables_mp' => $this->responsablesMp($recintoId),
            'programa_anual' => $this->programaAnual($anio, $recintoId),
            'tipos_norma' => $this->tiposNorma($recintoId, $servicioClinicoId),
        ];
    }

    /**
     * RF-72 (§7.2–7.3): estado del programa anual del recinto filtrado o, sin filtro, de cada
     * recinto activo visible para el usuario.
     */
    private function programaAnual(int $anio, ?int $recintoId): string
    {
        $recintos = Recinto::query()
            ->when(
                $recintoId !== null,
                fn ($query) => $query->whereKey($recintoId),
                fn ($query) => $query->where('activo', true),
            )
            ->with(['programasAnualesMantenimiento' => fn ($query) => $query->where('anio', $anio)])
            ->orderBy('nombre')
            ->get();

        $estado = fn (Recinto $recinto): string => $recinto->programasAnualesMantenimiento->first()?->descripcion() ?? 'sin registro';

        if ($recintoId !== null) {
            return ucfirst($recintos->first() !== null ? $estado($recintos->first()) : 'sin registro');
        }

        return $recintos->map(fn (Recinto $recinto): string => "{$recinto->nombre}: {$estado($recinto)}")->implode('; ');
    }

    /**
     * RF-75 ("Definiciones"): equipos críticos activos por tipo de la norma, más los sin clasificar.
     *
     * @return array<string, int>
     */
    private function tiposNorma(?int $recintoId, ?int $servicioClinicoId): array
    {
        $porTipo = Equipo::query()
            ->where('criticidad', Criticidad::Critico)
            ->where('activo', true)
            ->delAlcance($recintoId, $servicioClinicoId)
            ->get(['tipo_critico_norma'])
            ->countBy(fn (Equipo $equipo): string => $equipo->tipo_critico_norma?->getLabel() ?? 'Sin clasificar');

        return collect(TipoEquipoCritico::cases())
            ->mapWithKeys(fn (TipoEquipoCritico $tipo): array => [$tipo->getLabel() => $porTipo[$tipo->getLabel()] ?? 0])
            ->put('Sin clasificar', $porTipo['Sin clasificar'] ?? 0)
            ->all();
    }

    /**
     * RF-70 (Res. Ex. 1341/2017 §7.1): responsable de MP del recinto filtrado o, sin filtro, de cada
     * recinto activo visible para el usuario (RF-63).
     */
    private function responsablesMp(?int $recintoId): string
    {
        $recintos = Recinto::query()
            ->when(
                $recintoId !== null,
                fn ($query) => $query->whereKey($recintoId),
                fn ($query) => $query->where('activo', true),
            )
            ->orderBy('nombre')
            ->get();

        if ($recintoId !== null) {
            return $recintos->first()?->descripcionResponsableMp() ?? 'Sin designar';
        }

        return $recintos
            ->map(fn (Recinto $recinto): string => "{$recinto->nombre}: ".($recinto->descripcionResponsableMp() ?? 'sin designar'))
            ->implode('; ');
    }

    /**
     * @param  Collection<int, EjecucionMensual>  $delEquipo
     * @return array{recinto: string, servicio_clinico: string, equipo: string, marca: ?string, modelo: ?string, n_inventario: string, frecuencia_anual: int, periodicidad_fabricante: bool, programadas: int, realizadas: int, reprogramadas_pendientes: int, cumple: bool}
     */
    private function filaEquipo(Collection $delEquipo): array
    {
        $plan = $delEquipo->first()->planMantenimiento;
        $equipo = $plan->equipo;
        $realizadas = $delEquipo->where('estado', EstadoEjecucion::Realizado)->count();

        return [
            'recinto' => (string) $equipo->recinto?->nombre,
            'servicio_clinico' => (string) $equipo->servicioClinico?->nombre,
            'equipo' => $equipo->nombre,
            'marca' => $equipo->marca,
            'modelo' => $equipo->modelo,
            'n_inventario' => $equipo->n_inventario,
            'frecuencia_anual' => (int) $plan->frecuencia_anual->value,
            // RF-69: bajo el mínimo de 2 solo puede estar un crítico en garantía (fabricante).
            'periodicidad_fabricante' => ! Criticidad::Critico->admiteFrecuencia($plan->frecuencia_anual),
            'programadas' => $delEquipo->count(),
            'realizadas' => $realizadas,
            'reprogramadas_pendientes' => $delEquipo->where('estado', EstadoEjecucion::Reprogramado)->count(),
            'cumple' => $realizadas === $delEquipo->count(),
        ];
    }

    /**
     * @return array{recinto: string, equipo: string, n_inventario: string, mes: int, causa: string, documento: ?string, plazo: string, estado: EstadoEjecucion, vencida: bool, retiro_uso: ?string}
     */
    private function filaReprogramacion(EjecucionMensual $ejecucion, int $anio): array
    {
        $equipo = $ejecucion->planMantenimiento->equipo;
        $plazo = EjecucionMensual::plazoParaMes($anio, $ejecucion->mes);

        return [
            'recinto' => (string) $equipo->recinto?->nombre,
            'equipo' => $equipo->nombre,
            'n_inventario' => $equipo->n_inventario,
            'mes' => $ejecucion->mes,
            'causa' => (string) $ejecucion->causa_reprogramacion,
            // RF-74 (§7.4.ii): referencia al documento formal de la justificación.
            'documento' => $ejecucion->documento_justificacion,
            'plazo' => $plazo->format('d-m-Y'),
            'estado' => $ejecucion->estado,
            'vencida' => $ejecucion->estado === EstadoEjecucion::Reprogramado && $plazo->endOfDay()->isPast(),
            // RF-73 (§7.4.iii): retiro de uso registrado después del mes reprogramado.
            'retiro_uso' => $equipo->retirosUso
                ->first(fn (RetiroUso $retiro): bool => $retiro->fecha_retiro->greaterThanOrEqualTo(CarbonImmutable::create($anio, $ejecucion->mes, 1)))
                ?->fecha_retiro->format('d-m-Y'),
        ];
    }
}
