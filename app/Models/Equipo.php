<?php

namespace App\Models;

use App\Enums\Criticidad;
use App\Enums\EstadoEquipo;
use App\Enums\Propiedad;
use Database\Factories\EquipoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
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
    'en_garantia',
    'garantia_anio_vencimiento',
    'bajo_plan_mp',
    'anio_ingreso_plan',
    'activo',
])]
class Equipo extends Model implements AuditableContract
{
    /** @use HasFactory<EquipoFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'propiedad' => Propiedad::class,
            'estado' => EstadoEquipo::class,
            'criticidad' => Criticidad::class,
            'en_garantia' => 'boolean',
            'bajo_plan_mp' => 'boolean',
            'activo' => 'boolean',
        ];
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
