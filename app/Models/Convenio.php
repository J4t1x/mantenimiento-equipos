<?php

namespace App\Models;

use Database\Factories\ConvenioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable([
    'proveedor_id',
    'nombre',
    'n_resolucion',
    'fecha_resolucion',
    'fecha_expiracion',
    'monto_anual',
    'subasignacion_sigfe',
    'activo',
])]
class Convenio extends Model implements AuditableContract
{
    /** @use HasFactory<ConvenioFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'fecha_resolucion' => 'date',
            'fecha_expiracion' => 'date',
            'monto_anual' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function planesMantenimiento(): HasMany
    {
        return $this->hasMany(PlanMantenimiento::class);
    }

    public function ejecucionesMensuales(): HasMany
    {
        return $this->hasMany(ConvenioEjecucionMensual::class);
    }
}
