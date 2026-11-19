<?php

namespace App\Filament\Support;

use App\Enums\Criticidad;
use App\Enums\EstadoEjecucion;
use App\Enums\TipoMantenimiento;
use App\Filament\Resources\PlanMantenimientos\PlanMantenimientoResource;
use App\Models\EjecucionMensual;
use App\Models\ServicioClinico;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * SPEC-ME-023: lógica del calendario de mantenciones, compartida por la página del recurso
 * (`CalendarioPlanes`) y el widget del Escritorio (`CalendarioMantencionesWidget`).
 *
 * Vista anual (un resumen por mes) y vista de un mes (calendario por días con las realizadas en su
 * fecha real, y aparte las pendientes y las realizadas sin fecha, porque la bitácora programa por
 * mes). Los registros los acota el global scope por recinto de {@see EjecucionMensual} (RF-63), y
 * "atrasada" usa la regla del Escritorio ({@see EjecucionMensual::primerMesEnPlazo()}).
 *
 * La clase que lo usa declara `$mes`, `$servicio`, `$criticidad` y `$tipo`, e implementa
 * {@see anioCalendario()} y {@see puedeCambiarDeAnio()}.
 */
trait CalendarioDeMantenciones
{
    abstract public function anioCalendario(): int;

    /**
     * Si la navegación entre meses puede pasar a otro año. En el Escritorio el año lo fija su selector.
     */
    abstract public function puedeCambiarDeAnio(): bool;

    /**
     * Meses que muestra la vista anual. En el Escritorio, los del período elegido.
     *
     * @return array<int, int>
     */
    public function mesesVisibles(): array
    {
        return range(1, 12);
    }

    public function verMes(int $mes): void
    {
        $this->mes = max(1, min(12, $mes));
    }

    public function verAnio(): void
    {
        $this->mes = null;
    }

    public function mesAnterior(): void
    {
        if ($this->mes > 1) {
            $this->mes--;
        } elseif ($this->puedeCambiarDeAnio()) {
            $this->anio--;
            $this->mes = 12;
        }
    }

    public function mesSiguiente(): void
    {
        if ($this->mes < 12) {
            $this->mes++;
        } elseif ($this->puedeCambiarDeAnio()) {
            $this->anio++;
            $this->mes = 1;
        }
    }

    public function limpiarFiltros(): void
    {
        $this->servicio = null;
        $this->criticidad = null;
        $this->tipo = null;
    }

    /**
     * Meses visibles con su resumen, para la vista anual.
     *
     * @return array<int, array{mes: int, nombre: string, programadas: int, atrasadas: int, reprogramadas: int, realizadas: int, total: int, avance: int, esActual: bool}>
     */
    public function resumenAnual(): array
    {
        $anio = $this->anioCalendario();
        $conteos = $this->consultaBase()
            ->toBase()
            ->selectRaw('ejecuciones_mensuales.mes, ejecuciones_mensuales.estado, count(*) as total')
            ->groupBy('ejecuciones_mensuales.mes', 'ejecuciones_mensuales.estado')
            ->get();

        $primerMesEnPlazo = EjecucionMensual::primerMesEnPlazo($anio);
        $meses = [];

        foreach ($this->mesesVisibles() as $mes) {
            $delMes = $conteos->where('mes', $mes);
            $contar = fn (EstadoEjecucion $estado): int => (int) $delMes->where('estado', $estado->value)->sum('total');
            $programadas = $contar(EstadoEjecucion::Programado);
            $atrasadas = $mes < $primerMesEnPlazo ? $programadas : 0;
            $realizadas = $contar(EstadoEjecucion::Realizado);
            $reprogramadas = $contar(EstadoEjecucion::Reprogramado);
            $total = $programadas + $realizadas + $reprogramadas;

            $meses[$mes] = [
                'mes' => $mes,
                'nombre' => $this->nombreMes($mes),
                'programadas' => $programadas - $atrasadas,
                'atrasadas' => $atrasadas,
                'reprogramadas' => $reprogramadas,
                'realizadas' => $realizadas,
                'total' => $total,
                'avance' => $total > 0 ? (int) round($realizadas * 100 / $total) : 0,
                'esActual' => $anio === now()->year && $mes === now()->month,
            ];
        }

        return $meses;
    }

    /**
     * Semanas del mes (lunes a domingo) con las mantenciones realizadas en cada día.
     *
     * @return array<int, array<int, array{fecha: CarbonImmutable, delMes: bool, esHoy: bool, realizadas: Collection<int, array<string, mixed>>}>>
     */
    public function semanasDelMes(): array
    {
        $inicio = CarbonImmutable::create($this->anioCalendario(), $this->mes, 1);
        $fin = $inicio->endOfMonth();

        $realizadasPorDia = EjecucionMensual::query()
            ->with(['planMantenimiento.equipo.servicioClinico'])
            ->where('estado', EstadoEjecucion::Realizado)
            ->whereBetween('fecha_real', [$inicio->toDateString(), $fin->toDateString()])
            ->whereHas('planMantenimiento', fn (Builder $plan) => $this->filtrarPlan($plan))
            ->get()
            ->map(fn (EjecucionMensual $ejecucion): array => $this->item($ejecucion))
            ->groupBy(fn (array $item): string => $item['fechaReal']);

        $semanas = [];
        $dia = $inicio->startOfWeek(CarbonImmutable::MONDAY);
        $ultimo = $fin->endOfWeek(CarbonImmutable::SUNDAY);

        while ($dia <= $ultimo) {
            $semanas[$dia->isoWeek()][] = [
                'fecha' => $dia,
                'delMes' => $dia->month === $this->mes,
                'esHoy' => $dia->isToday(),
                'realizadas' => $realizadasPorDia->get($dia->toDateString(), collect()),
            ];
            $dia = $dia->addDay();
        }

        return array_values($semanas);
    }

    /**
     * Mantenciones del mes que no van en un día del calendario: pendientes (atrasadas,
     * reprogramadas y programadas) y realizadas sin fecha real.
     *
     * @return array{pendientes: Collection<int, array<string, mixed>>, sinFecha: Collection<int, array<string, mixed>>, totales: array<string, int>}
     */
    public function detalleDelMes(): array
    {
        $items = $this->consultaBase()
            ->with(['planMantenimiento.equipo.servicioClinico'])
            ->where('ejecuciones_mensuales.mes', $this->mes)
            ->select('ejecuciones_mensuales.*')
            ->get()
            ->map(fn (EjecucionMensual $ejecucion): array => $this->item($ejecucion));

        $orden = ['atrasada' => 0, 'reprogramada' => 1, 'programada' => 2];

        return [
            'pendientes' => $items
                ->where('tipo', '!=', 'realizada')
                ->sortBy(fn (array $item): string => $orden[$item['tipo']].$item['inventario'])
                ->values(),
            'sinFecha' => $items->where('tipo', 'realizada')->whereNull('fechaReal')->sortBy('inventario')->values(),
            'totales' => [
                'realizadas' => $items->where('tipo', 'realizada')->count(),
                'programadas' => $items->where('tipo', 'programada')->count(),
                'atrasadas' => $items->where('tipo', 'atrasada')->count(),
                'reprogramadas' => $items->where('tipo', 'reprogramada')->count(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function opcionesServicio(): array
    {
        return ServicioClinico::query()
            ->whereHas('equipos', fn (Builder $equipo) => $equipo->whereHas('planesMantenimiento', fn (Builder $plan) => $plan->where('anio', $this->anioCalendario())))
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function opcionesCriticidad(): array
    {
        return collect(Criticidad::cases())->mapWithKeys(fn (Criticidad $caso): array => [$caso->value => $caso->getLabel()])->all();
    }

    /**
     * @return array<string, string>
     */
    public function opcionesTipo(): array
    {
        return collect(TipoMantenimiento::cases())->mapWithKeys(fn (TipoMantenimiento $caso): array => [$caso->value => $caso->getLabel()])->all();
    }

    public function hayFiltros(): bool
    {
        return filled($this->servicio) || filled($this->criticidad) || filled($this->tipo);
    }

    public function nombreMes(int $mes): string
    {
        return ucfirst(CarbonImmutable::create(2000, $mes, 1)->locale('es')->translatedFormat('F'));
    }

    /**
     * Ejecuciones con mantención (no "Sin programar") de los planes del año, con los filtros.
     *
     * @return Builder<EjecucionMensual>
     */
    protected function consultaBase(): Builder
    {
        return EjecucionMensual::query()
            ->join('planes_mantenimiento', 'planes_mantenimiento.id', '=', 'ejecuciones_mensuales.plan_mantenimiento_id')
            ->join('equipos', 'equipos.id', '=', 'planes_mantenimiento.equipo_id')
            ->whereNull('planes_mantenimiento.deleted_at')
            ->whereNull('equipos.deleted_at')
            ->where('equipos.activo', true)
            ->where('planes_mantenimiento.anio', $this->anioCalendario())
            ->where('ejecuciones_mensuales.estado', '!=', EstadoEjecucion::SinProgramar)
            ->when(filled($this->servicio), fn (Builder $query) => $query->where('equipos.servicio_clinico_id', $this->servicio))
            ->when(filled($this->criticidad), fn (Builder $query) => $query->where('equipos.criticidad', $this->criticidad))
            ->when(filled($this->tipo), fn (Builder $query) => $query->where('planes_mantenimiento.tipo_mantenimiento', $this->tipo));
    }

    protected function filtrarPlan(Builder $plan): Builder
    {
        return $plan
            ->when(filled($this->tipo), fn (Builder $query) => $query->where('tipo_mantenimiento', $this->tipo))
            ->whereHas('equipo', fn (Builder $equipo) => $equipo
                ->where('activo', true)
                ->when(filled($this->servicio), fn (Builder $query) => $query->where('servicio_clinico_id', $this->servicio))
                ->when(filled($this->criticidad), fn (Builder $query) => $query->where('criticidad', $this->criticidad)));
    }

    /**
     * @return array{tipo: string, etiqueta: string, inventario: string, equipo: string, servicio: ?string, critico: bool, fechaReal: ?string, mes: int, url: string}
     */
    protected function item(EjecucionMensual $ejecucion): array
    {
        $plan = $ejecucion->planMantenimiento;
        $equipo = $plan->equipo;

        $tipo = match ($ejecucion->estado) {
            EstadoEjecucion::Realizado => 'realizada',
            EstadoEjecucion::Reprogramado => 'reprogramada',
            default => $ejecucion->mes < EjecucionMensual::primerMesEnPlazo($plan->anio) ? 'atrasada' : 'programada',
        };

        return [
            'tipo' => $tipo,
            'etiqueta' => ['realizada' => 'Realizada', 'reprogramada' => 'Reprogramada', 'atrasada' => 'Atrasada', 'programada' => 'Programada'][$tipo],
            'inventario' => (string) $equipo->n_inventario,
            'equipo' => (string) $equipo->nombre,
            'servicio' => $equipo->servicioClinico?->nombre,
            'critico' => $equipo->criticidad === Criticidad::Critico,
            'fechaReal' => $ejecucion->fecha_real?->toDateString(),
            'mes' => (int) $ejecucion->mes,
            'url' => PlanMantenimientoResource::getUrl('view', ['record' => $plan]),
        ];
    }
}
