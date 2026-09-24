<?php

namespace App\Models;

use Database\Factories\ServicioClinicoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'activo'])]
class ServicioClinico extends Model
{
    /** @use HasFactory<ServicioClinicoFactory> */
    use HasFactory;

    protected $table = 'servicios_clinicos';

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }
}
