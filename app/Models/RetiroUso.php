<?php

namespace App\Models;

use App\Actions\RegistrarRetiroUsoAction;
use App\Models\Contracts\AcotadoPorRecinto;
use App\Models\Scopes\AlcanceRecintoScope;
use App\Support\AlcanceRecinto;
use Database\Factories\RetiroUsoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * RF-73 del SRS (Res. Ex. 1341/2017 §7.4.iii): retiro de uso de un equipo, con la evidencia escrita
 * que exige la norma, y su reingreso. Se registra con {@see RegistrarRetiroUsoAction}.
 */
#[Fillable([
    'equipo_id',
    'fecha_retiro',
    'motivo',
    'documento_retiro',
    'fecha_reingreso',
    'documento_reingreso',
    'registrado_por_id',
])]
#[ScopedBy([AlcanceRecintoScope::class])]
class RetiroUso extends Model implements AcotadoPorRecinto, AuditableContract
{
    /** @use HasFactory<RetiroUsoFactory> */
    use Auditable, HasFactory;

    protected $table = 'retiros_uso';

    protected function casts(): array
    {
        return [
            'fecha_retiro' => 'date',
            'fecha_reingreso' => 'date',
        ];
    }

    /**
     * Retiros sin reingreso: el equipo sigue fuera de uso.
     */
    #[Scope]
    protected function abiertos(Builder $query): void
    {
        $query->whereNull($this->qualifyColumn('fecha_reingreso'));
    }

    public function restringirARecintos(Builder $query, array $recintoIds): void
    {
        $query->whereIn($this->qualifyColumn('equipo_id'), AlcanceRecinto::equiposDeRecintos($recintoIds));
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
