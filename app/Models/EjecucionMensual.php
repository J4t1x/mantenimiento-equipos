<?php

namespace App\Models;

use App\Enums\Criticidad;
use App\Enums\EstadoEjecucion;
use App\Exceptions\EquipoNoAsignadoException;
use App\Exceptions\ReprogramacionSinCausaException;
use App\Exceptions\TransicionBitacoraInvalidaException;
use App\Models\Contracts\AcotadoPorRecinto;
use App\Models\Scopes\AlcanceRecintoScope;
use App\Support\AlcanceRecinto;
use App\Support\AlcanceTecnicoInterno;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable(['plan_mantenimiento_id', 'mes', 'estado', 'fecha_real', 'observaciones', 'documento_justificacion'])]
#[ScopedBy([AlcanceRecintoScope::class])]
class EjecucionMensual extends Model implements AcotadoPorRecinto, AuditableContract
{
    use Auditable;

    /**
     * RN-09 (Res. Ex. 1341/2017 §7.4): plazo máximo de una reprogramación.
     */
    public const DIAS_PLAZO_REPROGRAMACION = 30;

    protected $table = 'ejecuciones_mensuales';

    protected function casts(): array
    {
        return [
            'estado' => EstadoEjecucion::class,
            'fecha_real' => 'date',
        ];
    }

    public function restringirARecintos(Builder $query, array $recintoIds): void
    {
        $query->whereIn(
            $this->qualifyColumn('plan_mantenimiento_id'),
            DB::table('planes_mantenimiento')->select('id')->whereIn('equipo_id', AlcanceRecinto::equiposDeRecintos($recintoIds)),
        );
    }

    /**
     * RF-66 (RN-09): meses reprogramados de equipos críticos activos cuyo plazo de 30 días ya
     * venció sin quedar "Realizado", y que por norma corresponde retirar de uso. Como la bitácora
     * registra el mes y no el día programado, el plazo se cuenta desde el último día del mes: un
     * mes está vencido si terminó antes del mes que contiene la fecha de hoy menos 30 días. La
     * comparación se hace como `año * 12 + mes` para no depender de funciones de fecha del motor.
     */
    #[Scope]
    protected function reprogramacionesVencidas(Builder $query, ?CarbonInterface $hoy = null): void
    {
        $limite = ($hoy ?? now())->toImmutable()->subDays(self::DIAS_PLAZO_REPROGRAMACION);
        $mesLimite = $limite->year * 12 + $limite->month;

        $query
            ->where($this->qualifyColumn('estado'), EstadoEjecucion::Reprogramado)
            ->whereHas('planMantenimiento', fn (Builder $plan) => $plan
                ->whereRaw('planes_mantenimiento.anio * 12 + ejecuciones_mensuales.mes < ?', [$mesLimite])
                ->whereHas('equipo', fn (Builder $equipo) => $equipo
                    ->where('criticidad', Criticidad::Critico)
                    ->where('activo', true)));
    }

    /**
     * RF-66: fecha hasta la que se puede realizar una mantención reprogramada (último día del mes
     * más 30 días, mismo criterio que {@see reprogramacionesVencidas()}).
     */
    public function plazoReprogramacion(): ?CarbonImmutable
    {
        $anio = $this->planMantenimiento?->anio;

        if ($this->estado !== EstadoEjecucion::Reprogramado || $anio === null) {
            return null;
        }

        return self::plazoParaMes($anio, $this->mes);
    }

    public static function plazoParaMes(int $anio, int $mes): CarbonImmutable
    {
        return CarbonImmutable::create($anio, $mes, 1)
            ->endOfMonth()
            ->startOfDay()
            ->addDays(self::DIAS_PLAZO_REPROGRAMACION);
    }

    public function planMantenimiento(): BelongsTo
    {
        return $this->belongsTo(PlanMantenimiento::class);
    }

    /**
     * RN-02 (RNF-04, defensa en profundidad): guarda de última instancia a nivel de modelo, para
     * que la transición inválida se rechace sin importar la vía de entrada (Filament, tinker,
     * seeder, futura API) — no solo desde el formulario.
     */
    protected static function booted(): void
    {
        static::saving(function (self $ejecucion): void {
            if (! $ejecucion->isDirty('estado')) {
                return;
            }

            $estadoAnterior = $ejecucion->exists
                ? EstadoEjecucion::tryFrom((string) $ejecucion->getRawOriginal('estado'))
                : null;

            if (! EstadoEjecucion::esTransicionValida($estadoAnterior, $ejecucion->estado)) {
                throw TransicionBitacoraInvalidaException::porFaltaDeProgramacion();
            }
        });

        static::saving(function (self $ejecucion): void {
            if ($ejecucion->estado === EstadoEjecucion::Reprogramado
                && $ejecucion->isDirty(['estado', 'observaciones'])
                && blank($ejecucion->observaciones)) {
                throw ReprogramacionSinCausaException::porFaltaDeCausa();
            }

            // RF-68: la causa se conserva aunque el mes pase después a "Realizado".
            if ($ejecucion->estado === EstadoEjecucion::Reprogramado && $ejecucion->isDirty(['estado', 'observaciones'])) {
                $ejecucion->causa_reprogramacion = $ejecucion->observaciones;
            }
        });

        static::saving(function (self $ejecucion): void {
            if ($ejecucion->isDirty('plan_mantenimiento_id')) {
                AlcanceRecinto::verificarEquipo(
                    PlanMantenimiento::withoutGlobalScopes()->whereKey($ejecucion->plan_mantenimiento_id)->value('equipo_id'),
                );
            }
        });

        static::saving(function (self $ejecucion): void {
            if (! AlcanceTecnicoInterno::puedeGestionarPlan($ejecucion->planMantenimiento)) {
                throw EquipoNoAsignadoException::porFaltaDeAsignacion();
            }
        });
    }
}
