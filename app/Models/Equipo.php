<?php

namespace App\Models;

use App\Enums\Criticidad;
use App\Enums\EstadoEquipo;
use App\Enums\FrecuenciaAnual;
use App\Enums\Propiedad;
use App\Enums\TipoEquipoCritico;
use App\Exceptions\FrecuenciaInsuficienteException;
use App\Exceptions\TipoCriticoSinCriticidadException;
use App\Models\Contracts\AcotadoPorRecinto;
use App\Models\Scopes\AlcanceRecintoScope;
use App\Support\AlcanceRecinto;
use Database\Factories\EquipoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable([
    'recinto_id',
    'servicio_clinico_id',
    'clase_id',
    'subclase_id',
    'nombre',
    'marca',
    'modelo',
    'serie',
    'n_inventario',
    'anio_adquisicion',
    'vida_util_anios',
    'propiedad',
    'estado',
    'criticidad',
    'tipo_critico_norma',
    'en_garantia',
    'garantia_anio_vencimiento',
    'bajo_plan_mp',
    'anio_ingreso_plan',
    'activo',
])]
#[ScopedBy([AlcanceRecintoScope::class])]
class Equipo extends Model implements AcotadoPorRecinto, AuditableContract
{
    /** @use HasFactory<EquipoFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'propiedad' => Propiedad::class,
            'estado' => EstadoEquipo::class,
            'criticidad' => Criticidad::class,
            'tipo_critico_norma' => TipoEquipoCritico::class,
            'en_garantia' => 'boolean',
            'bajo_plan_mp' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * RF-63 (RNF-04, defensa en profundidad): un usuario con recintos asignados no puede crear ni
     * mover un equipo a otro recinto. RF-65/RF-69 (RN-08): un cambio de criticidad o de garantía no
     * puede dejar un plan de MP vigente con una frecuencia que el equipo ya no admite (p. ej. pasar
     * a Crítico, o quitarle la garantía a un crítico con frecuencia 1).
     */
    protected static function booted(): void
    {
        // RF-75 (Res. Ex. 1341/2017, "Definiciones"): un tipo de la norma exige criticidad Crítico.
        static::saving(function (self $equipo): void {
            if ($equipo->isDirty(['tipo_critico_norma', 'criticidad']) && ! $equipo->criticidadCumpleTipoNorma()) {
                throw TipoCriticoSinCriticidadException::paraTipo();
            }
        });

        static::saving(function (self $equipo): void {
            if ($equipo->isDirty('recinto_id')) {
                AlcanceRecinto::verificarRecinto($equipo->recinto_id);
            }
        });

        static::saving(function (self $equipo): void {
            if ($equipo->exists
                && $equipo->isDirty(['criticidad', 'en_garantia', 'garantia_anio_vencimiento'])
                && $equipo->tienePlanVigenteQueNoAdmite()) {
                throw FrecuenciaInsuficienteException::paraEquipoCritico();
            }
        });
    }

    /**
     * RF-75: false si el equipo es de uno de los 6 tipos de la norma y no está marcado Crítico.
     */
    public function criticidadCumpleTipoNorma(): bool
    {
        return ! $this->tipo_critico_norma?->exigeCriticidadCritica()
            || $this->criticidad === Criticidad::Critico;
    }

    /**
     * RF-69 (Res. Ex. 1341/2017 §7.4.i): garantía vigente en el año dado. Sin año de vencimiento
     * registrado, se considera vigente mientras el equipo figure en garantía.
     */
    public function tieneGarantiaVigenteEn(int $anio): bool
    {
        return (bool) $this->en_garantia
            && ($this->garantia_anio_vencimiento === null || (int) $this->garantia_anio_vencimiento >= $anio);
    }

    /**
     * RF-65/RF-69 (RN-08): la frecuencia debe cumplir el mínimo de la criticidad, salvo en un equipo
     * con garantía vigente en el año del plan, que puede seguir la periodicidad del fabricante.
     */
    public function admiteFrecuencia(FrecuenciaAnual $frecuencia, int $anio): bool
    {
        return $this->criticidad === null
            || $this->criticidad->admiteFrecuencia($frecuencia)
            || $this->tieneGarantiaVigenteEn($anio);
    }

    /**
     * RF-65/RF-69: true si el equipo, con sus atributos actuales (aunque no estén guardados), tiene un
     * plan del año en curso o posterior cuya frecuencia no admite. Los planes de años anteriores no
     * se revisan: son historia.
     */
    public function tienePlanVigenteQueNoAdmite(): bool
    {
        if (! $this->exists) {
            return false;
        }

        return $this->planesMantenimiento()
            ->where('anio', '>=', now()->year)
            ->get(['anio', 'frecuencia_anual'])
            ->contains(fn (PlanMantenimiento $plan): bool => ! $this->admiteFrecuencia($plan->frecuencia_anual, (int) $plan->anio));
    }

    public function restringirARecintos(Builder $query, array $recintoIds): void
    {
        $query->whereIn($this->qualifyColumn('recinto_id'), $recintoIds);
    }

    /**
     * RF-71 (Res. Ex. 1341/2017 §7.2): el programa anual considera como mínimo el catastro de
     * equipos críticos vigente, así que todo crítico activo debe tener plan del año.
     */
    #[Scope]
    protected function criticosSinPlan(Builder $query, ?int $anio = null): void
    {
        $query
            ->where($this->qualifyColumn('criticidad'), Criticidad::Critico)
            ->where($this->qualifyColumn('activo'), true)
            ->whereDoesntHave('planesMantenimiento', fn (Builder $plan) => $plan->where('anio', $anio ?? now()->year));
    }

    /**
     * RF-54: acota al recinto y/o servicio clínico dado; un filtro en `null` no restringe nada.
     */
    #[Scope]
    protected function delAlcance(Builder $query, ?int $recintoId = null, ?int $servicioClinicoId = null): void
    {
        $query
            ->when($recintoId !== null, fn (Builder $query) => $query->where('recinto_id', $recintoId))
            ->when($servicioClinicoId !== null, fn (Builder $query) => $query->where('servicio_clinico_id', $servicioClinicoId));
    }

    public function recinto(): BelongsTo
    {
        return $this->belongsTo(Recinto::class);
    }

    public function servicioClinico(): BelongsTo
    {
        return $this->belongsTo(ServicioClinico::class);
    }

    public function clase(): BelongsTo
    {
        return $this->belongsTo(ClaseEquipo::class, 'clase_id');
    }

    public function subclase(): BelongsTo
    {
        return $this->belongsTo(ClaseEquipo::class, 'subclase_id');
    }

    public function planesMantenimiento(): HasMany
    {
        return $this->hasMany(PlanMantenimiento::class);
    }

    /**
     * RF-73: historial de retiros de uso del equipo.
     */
    public function retirosUso(): HasMany
    {
        return $this->hasMany(RetiroUso::class);
    }

    public function retiroUsoAbierto(): ?RetiroUso
    {
        return $this->retirosUso()->abiertos()->latest('fecha_retiro')->first();
    }

    public function mantenimientosCorrectivos(): HasMany
    {
        return $this->hasMany(MantenimientoCorrectivo::class);
    }

    /**
     * Vida útil residual (RF-09): calculada en tiempo de lectura, no persistida
     * (MODELO-DATOS §3.5). Puede resultar negativa si el equipo superó su vida útil.
     */
    protected function vidaUtilResidual(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->vida_util_anios !== null && $this->anio_adquisicion !== null
                ? $this->vida_util_anios - (now()->year - $this->anio_adquisicion)
                : null,
        );
    }
}
