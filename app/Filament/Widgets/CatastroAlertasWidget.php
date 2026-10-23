<?php

namespace App\Filament\Widgets;

use App\Enums\Criticidad;
use App\Enums\EstadoEjecucion;
use App\Enums\EstadoEquipo;
use App\Filament\Resources\EjecucionesMensuales\EjecucionMensualResource;
use App\Filament\Resources\EjecucionesMensuales\Tables\EjecucionesMensualesTable;
use App\Filament\Resources\Equipos\EquipoResource;
use App\Filament\Resources\Equipos\Tables\EquiposTable;
use App\Filament\Resources\Recintos\RecintoResource;
use App\Filament\Resources\Recintos\Tables\RecintosTable;
use App\Filament\Support\RecibePeriodoDelEscritorio;
use App\Filament\Support\SoloAlertasPendientes;
use App\Models\EjecucionMensual;
use App\Models\Equipo;
use App\Models\Recinto;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * RF-46 (Módulo 11): tres indicadores de catastro que hoy solo se ven equipo por equipo, en su
 * ficha — este widget los agrega para el Escritorio de Encargado de Mantención y Jefatura. Los
 * tres son consultas directas sobre `Equipo`, sin cálculo nuevo: "Sin evaluar" es un valor propio
 * de `estado` (RF-12), la garantía por vencer usa los mismos campos que muestra
 * `EquipoInfolist` (`en_garantia`/`garantia_anio_vencimiento`), y la vida útil residual replica en
 * SQL la misma fórmula del accessor `Equipo::vidaUtilResidual()` (RF-09) para poder filtrar por
 * ella sin cargar todo el catastro en memoria.
 *
 * RF-61 (Módulo 13): las tres tarjetas enlazan al listado de Equipos; "Sin evaluar" llega con el
 * filtro de estado ya aplicado. Garantía y vida útil llegan sin filtro, porque el listado no tiene
 * uno para esos campos.
 *
 * RF-66 (Módulo 15, Res. Ex. 1341/2017 §7.4): cuarta tarjeta con los equipos críticos cuya MP
 * reprogramada no se realizó dentro de 30 días y que por norma corresponde retirar de uso. Solo
 * alerta: no bloquea ni da de baja el equipo. Enlaza a la Bitácora con el filtro ya aplicado.
 *
 * RF-71 (Módulo 16, §7.2): quinta tarjeta con los equipos críticos activos sin plan de MP del año,
 * que el programa anual debe incluir como mínimo. Enlaza a Equipos con el filtro ya aplicado.
 *
 * RF-72 (Módulo 16, §7.2–7.3): desde abril (el plazo de la norma es marzo), una tarjeta con los
 * recintos activos sin programa anual validado por la Dirección. Enlaza a Recintos filtrado.
 *
 * Año del Escritorio (corrección de RF-21, SPEC-ME-002): garantía, vida útil residual, críticos sin
 * plan y programa sin validar se calculan para el año elegido. "Sin evaluar" y "a retirar de uso"
 * son alertas operativas sobre el estado actual y no dependen del año. Programa sin validar se
 * muestra desde abril en el año en curso, siempre en años pasados y nunca en años futuros. El
 * enlace de críticos sin plan y el de programa sin validar solo se ofrecen en el año en curso, que
 * es el que filtran los listados.
 *
 * RF-80 (Módulo 19): primera tarjeta con los equipos que tienen MP atrasada (meses "Programado" ya
 * vencidos sin registrar, ver `EjecucionMensual::atrasadas()`). Cuenta equipos, no meses, y se
 * marca en rojo si alguno es crítico. Enlaza a la Bitácora: con el filtro de atrasadas en el año en
 * curso, o con el año y el estado "Programado" en un año pasado.
 *
 * SPEC-ME-007: solo se muestran las tarjetas con casos, ordenadas por gravedad
 * (`SoloAlertasPendientes`).
 */
class CatastroAlertasWidget extends StatsOverviewWidget
{
    use RecibePeriodoDelEscritorio;

    /**
     * RF-57: ver el criterio de orden en `AlertasConveniosWidget::$sort`.
     */
    protected static ?int $sort = 11;

    protected ?string $heading = 'Catastro y mantenimiento';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view.catastro_alertas_widget');
    }

    protected function getStats(): array
    {
        $anio = $this->anioSeleccionado();

        $sinEvaluar = Equipo::where('activo', true)
            ->where('estado', EstadoEquipo::SinEvaluar)
            ->count();

        $garantiaPorVencer = Equipo::where('activo', true)
            ->where('en_garantia', true)
            ->where('garantia_anio_vencimiento', $anio)
            ->count();

        $vidaUtilNegativa = Equipo::where('activo', true)
            ->whereNotNull('anio_adquisicion')
            ->whereNotNull('vida_util_anios')
            ->whereRaw('anio_adquisicion + vida_util_anios < ?', [$anio])
            ->count();

        $criticosARetirar = EjecucionMensual::query()
            ->reprogramacionesVencidas()
            ->join('planes_mantenimiento', 'planes_mantenimiento.id', '=', 'ejecuciones_mensuales.plan_mantenimiento_id')
            ->distinct()
            ->count('planes_mantenimiento.equipo_id');

        $criticosSinPlan = Equipo::query()->criticosSinPlan($anio)->count();

        $atrasadas = fn () => EjecucionMensual::query()
            ->atrasadas($anio)
            ->join('planes_mantenimiento', 'planes_mantenimiento.id', '=', 'ejecuciones_mensuales.plan_mantenimiento_id');
        $equiposAtrasados = $atrasadas()->distinct()->count('planes_mantenimiento.equipo_id');
        $criticosAtrasados = $atrasadas()
            ->join('equipos', 'equipos.id', '=', 'planes_mantenimiento.equipo_id')
            ->where('equipos.criticidad', Criticidad::Critico)
            ->distinct()
            ->count('planes_mantenimiento.equipo_id');

        $puedeVerEquipos = EquipoResource::canViewAny();
        $urlEquipos = $puedeVerEquipos ? EquipoResource::getUrl('index') : null;

        $stats = [
            Stat::make('MP atrasada', $equiposAtrasados)
                ->description($criticosAtrasados > 0
                    ? "Equipos con meses programados sin registrar ({$criticosAtrasados} críticos)"
                    : 'Equipos con meses programados sin registrar')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color(match (true) {
                    $criticosAtrasados > 0 => 'danger',
                    $equiposAtrasados > 0 => 'warning',
                    default => 'success',
                })
                ->url($this->urlBitacoraAtrasadas($anio)),

            Stat::make('Equipos sin evaluar', $sinEvaluar)
                ->description('Estado "Sin evaluar"')
                ->icon(Heroicon::OutlinedQuestionMarkCircle)
                ->color($sinEvaluar > 0 ? 'warning' : 'success')
                ->url($puedeVerEquipos ? EquipoResource::getUrl('index', ['filters' => ['estado' => ['value' => EstadoEquipo::SinEvaluar->value]]]) : null),

            Stat::make($this->esAnioEnCurso() ? 'Garantía vence este año' : "Garantía vence en {$anio}", $garantiaPorVencer)
                ->description("Vencimiento en {$anio}")
                ->icon(Heroicon::OutlinedShieldExclamation)
                ->color($garantiaPorVencer > 0 ? 'warning' : 'success')
                ->url($urlEquipos),

            Stat::make('Vida útil residual negativa', $vidaUtilNegativa)
                ->description('Superaron su vida útil estimada')
                ->icon(Heroicon::OutlinedExclamationCircle)
                ->color($vidaUtilNegativa > 0 ? 'danger' : 'success')
                ->url($urlEquipos),

            Stat::make("Críticos sin plan {$anio}", $criticosSinPlan)
                ->description('El programa anual debe incluir todo crítico')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color($criticosSinPlan > 0 ? 'danger' : 'success')
                ->url($puedeVerEquipos && $this->esAnioEnCurso() ? EquipoResource::getUrl('index', ['filters' => [EquiposTable::FILTRO_CRITICOS_SIN_PLAN => ['isActive' => true]]]) : null),

            Stat::make('Críticos a retirar de uso', $criticosARetirar)
                ->description('Reprogramación sin realizar tras 30 días')
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color($criticosARetirar > 0 ? 'danger' : 'success')
                ->url(EjecucionMensualResource::canViewAny()
                    ? EjecucionMensualResource::getUrl('index', ['filters' => [EjecucionesMensualesTable::FILTRO_REPROGRAMACIONES_VENCIDAS => ['isActive' => true]]])
                    : null),
        ];

        if ($this->mostrarProgramaSinValidar($anio)) {
            $sinProgramaValidado = Recinto::query()->sinProgramaValidado($anio)->count();

            $stats[] = Stat::make("Programa {$anio} sin validar", $sinProgramaValidado)
                ->description('Recintos sin programa anual validado por la Dirección (plazo: marzo)')
                ->icon(Heroicon::OutlinedDocumentCheck)
                ->color($sinProgramaValidado > 0 ? 'danger' : 'success')
                ->url(RecintoResource::canViewAny() && $this->esAnioEnCurso()
                    ? RecintoResource::getUrl('index', ['filters' => [RecintosTable::FILTRO_PROGRAMA_SIN_VALIDAR => ['isActive' => true]]])
                    : null);
        }

        return SoloAlertasPendientes::filtrar($stats);
    }

    /**
     * El plazo de la norma para validar el programa es marzo: en el año en curso la alerta aparece
     * desde abril; en un año pasado el plazo ya venció, y en uno futuro todavía no corre.
     */
    private function mostrarProgramaSinValidar(int $anio): bool
    {
        if ($anio === now()->year) {
            return now()->month >= 4;
        }

        return $anio < now()->year;
    }

    private function urlBitacoraAtrasadas(int $anio): ?string
    {
        if (! EjecucionMensualResource::canViewAny() || $anio > now()->year) {
            return null;
        }

        $filtros = $this->esAnioEnCurso()
            ? [EjecucionesMensualesTable::FILTRO_ATRASADAS => ['isActive' => true]]
            : ['anio' => ['value' => $anio], 'estado' => ['value' => EstadoEjecucion::Programado->value]];

        return EjecucionMensualResource::getUrl('index', ['filters' => $filtros]);
    }
}
