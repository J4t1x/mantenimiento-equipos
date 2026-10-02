<?php

namespace App\Models\Scopes;

use App\Models\Contracts\AcotadoPorRecinto;
use App\Support\AlcanceRecinto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * RF-63 del SRS: acota toda consulta Eloquent del modelo a los recintos del usuario actual (ver
 * {@see AlcanceRecinto}). Como es un global scope, también aplica a la resolución de registros de
 * Filament (un registro de otro recinto responde 404), a los `Select` con `relationship()`, a los
 * filtros de tabla, a los widgets del Escritorio y a los exports.
 */
class AlcanceRecintoScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $recintoIds = AlcanceRecinto::recintoIds();

        if ($recintoIds !== null && $model instanceof AcotadoPorRecinto) {
            $model->restringirARecintos($builder, $recintoIds);
        }
    }
}
