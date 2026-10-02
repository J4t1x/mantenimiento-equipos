<?php

namespace App\Models\Contracts;

use App\Models\Scopes\AlcanceRecintoScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * RF-63 del SRS: modelos cuyas consultas se acotan a los recintos del usuario actual vía
 * {@see AlcanceRecintoScope}. Cada modelo define cómo llegar a su recinto.
 */
interface AcotadoPorRecinto
{
    /**
     * @param  array<int, int>  $recintoIds
     */
    public function restringirARecintos(Builder $query, array $recintoIds): void;
}
