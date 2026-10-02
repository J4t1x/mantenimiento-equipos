<?php

namespace App\Models;

use App\Models\Contracts\AcotadoPorRecinto;
use App\Models\Scopes\AlcanceRecintoScope;
use App\Support\AlcanceRecinto;
use Database\Factories\MantenimientoCorrectivoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable(['equipo_id', 'fecha', 'falla_descripcion', 'costo', 'tipo_gasto'])]
#[ScopedBy([AlcanceRecintoScope::class])]
class MantenimientoCorrectivo extends Model implements AcotadoPorRecinto, AuditableContract
{
    /** @use HasFactory<MantenimientoCorrectivoFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'mantenimientos_correctivos';

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'costo' => 'decimal:2',
        ];
    }

    /**
     * RF-63 (RNF-04): el correctivo lo registra el encargado del establecimiento del equipo.
     */
    protected static function booted(): void
    {
        static::saving(function (self $correctivo): void {
            if ($correctivo->isDirty('equipo_id')) {
                AlcanceRecinto::verificarEquipo($correctivo->equipo_id);
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
}
