<?php

namespace App\Models;

use App\Models\Contracts\AcotadoPorRecinto;
use App\Models\Scopes\AlcanceRecintoScope;
use Database\Factories\PresupuestoMantenimientoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * RF-76 del SRS: gasto programado anual de MP y MC de un recinto. Es la base del "programado" en
 * el detalle de gasto (`ObtenerDetalleGastoAction`).
 */
#[Fillable(['recinto_id', 'anio', 'gasto_programado_mp', 'gasto_programado_mc'])]
#[ScopedBy([AlcanceRecintoScope::class])]
class PresupuestoMantenimiento extends Model implements AcotadoPorRecinto, AuditableContract
{
    /** @use HasFactory<PresupuestoMantenimientoFactory> */
    use Auditable, HasFactory;

    protected $table = 'presupuestos_mantenimiento';

    protected function casts(): array
    {
        return [
            'gasto_programado_mp' => 'decimal:2',
            'gasto_programado_mc' => 'decimal:2',
        ];
    }

    public function restringirARecintos(Builder $query, array $recintoIds): void
    {
        $query->whereIn($this->qualifyColumn('recinto_id'), $recintoIds);
    }

    public function recinto(): BelongsTo
    {
        return $this->belongsTo(Recinto::class);
    }
}
