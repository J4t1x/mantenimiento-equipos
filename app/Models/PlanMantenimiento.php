<?php

namespace App\Models;

use App\Enums\FrecuenciaAnual;
use App\Enums\TipoMantenimiento;
use App\Exceptions\FrecuenciaInsuficienteException;
use App\Models\Contracts\AcotadoPorRecinto;
use App\Models\Scopes\AlcanceRecintoScope;
use App\Observers\PlanMantenimientoObserver;
use App\Support\AlcanceRecinto;
use Database\Factories\PlanMantenimientoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable([
    'equipo_id',
    'anio',
    'frecuencia_anual',
    'tipo_mantenimiento',
    'proveedor_id',
    'responsable_interno_id',
    'convenio_id',
    'costo_anual_referencia',
])]
#[ObservedBy(PlanMantenimientoObserver::class)]
#[ScopedBy([AlcanceRecintoScope::class])]
class PlanMantenimiento extends Model implements AcotadoPorRecinto, AuditableContract
{
    /** @use HasFactory<PlanMantenimientoFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'planes_mantenimiento';

    protected function casts(): array
    {
        return [
            'frecuencia_anual' => FrecuenciaAnual::class,
            'tipo_mantenimiento' => TipoMantenimiento::class,
            'costo_anual_referencia' => 'decimal:2',
        ];
    }

    /**
     * RF-63 (RNF-04): el equipo del plan debe ser de un recinto del usuario. RF-65/RF-69 (RN-08): un
     * equipo crítico no admite frecuencia 1, salvo con garantía vigente en el año del plan. Ambas se validan también en el formulario; esto es la
     * guarda de última instancia.
     */
    protected static function booted(): void
    {
        static::saving(function (self $plan): void {
            if ($plan->isDirty('equipo_id')) {
                AlcanceRecinto::verificarEquipo($plan->equipo_id);
            }
        });

        static::saving(function (self $plan): void {
            if (! $plan->isDirty(['equipo_id', 'anio', 'frecuencia_anual']) || $plan->frecuencia_anual === null) {
                return;
            }

            $equipo = Equipo::withoutGlobalScopes()->whereKey($plan->equipo_id)->first();

            if ($equipo !== null && ! $equipo->admiteFrecuencia($plan->frecuencia_anual, (int) $plan->anio)) {
                throw FrecuenciaInsuficienteException::paraEquipoCritico();
            }
        });
    }

    public function restringirARecintos(Builder $query, array $recintoIds): void
    {
        $query->whereIn($this->qualifyColumn('equipo_id'), AlcanceRecinto::equiposDeRecintos($recintoIds));
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function responsableInterno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_interno_id');
    }

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class);
    }

    public function ejecucionesMensuales(): HasMany
    {
        return $this->hasMany(EjecucionMensual::class);
    }
}
