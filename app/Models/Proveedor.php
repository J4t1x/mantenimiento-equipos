<?php

namespace App\Models;

use Database\Factories\ProveedorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'rut', 'contacto', 'activo'])]
class Proveedor extends Model
{
    /** @use HasFactory<ProveedorFactory> */
    use HasFactory;

    protected $table = 'proveedores';

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function convenios(): HasMany
    {
        return $this->hasMany(Convenio::class);
    }

    public function planesMantenimiento(): HasMany
    {
        return $this->hasMany(PlanMantenimiento::class);
    }
}
