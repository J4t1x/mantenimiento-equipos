<?php

namespace App\Models;

use Database\Factories\MantenimientoCorrectivoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable(['equipo_id', 'fecha', 'falla_descripcion', 'costo', 'tipo_gasto'])]
class MantenimientoCorrectivo extends Model implements AuditableContract
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

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }
}
