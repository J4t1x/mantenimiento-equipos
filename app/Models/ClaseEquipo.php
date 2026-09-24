<?php

namespace App\Models;

use Database\Factories\ClaseEquipoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'clase_padre_id', 'activo'])]
class ClaseEquipo extends Model
{
    /** @use HasFactory<ClaseEquipoFactory> */
    use HasFactory;

    protected $table = 'clases_equipo';

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function clasePadre(): BelongsTo
    {
        return $this->belongsTo(ClaseEquipo::class, 'clase_padre_id');
    }

    public function subclases(): HasMany
    {
        return $this->hasMany(ClaseEquipo::class, 'clase_padre_id');
    }

    public function equiposComoClase(): HasMany
    {
        return $this->hasMany(Equipo::class, 'clase_id');
    }

    public function equiposComoSubclase(): HasMany
    {
        return $this->hasMany(Equipo::class, 'subclase_id');
    }
}
