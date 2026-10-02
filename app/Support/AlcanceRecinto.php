<?php

namespace App\Support;

use App\Exceptions\RecintoFueraDeAlcanceException;
use App\Models\Scopes\AlcanceRecintoScope;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * RF-63 del SRS: un usuario con recintos asignados (encargado de mantención de un establecimiento)
 * solo ve y registra datos de esos recintos; uno sin recintos asignados (personal del
 * subdepartamento del SSA) ve todos. Sin usuario autenticado (consola, importador, colas) no hay
 * restricción.
 *
 * Punto único del criterio, reutilizado desde {@see AlcanceRecintoScope}
 * (consultas Eloquent), desde las consultas `DB::table()` de las Actions de indicadores, desde las
 * guardas de modelo (RNF-04, defensa en profundidad, igual que RN-02 y RF-20) y desde
 * `RecintoPolicy`.
 */
class AlcanceRecinto
{
    /**
     * Ids de los recintos visibles para el usuario actual, o `null` si no tiene restricción.
     *
     * @return array<int, int>|null
     */
    public static function recintoIds(): ?array
    {
        return self::recintoIdsDe(auth()->user());
    }

    /**
     * @return array<int, int>|null
     */
    public static function recintoIdsDe(?Authenticatable $usuario): ?array
    {
        if (! $usuario instanceof User) {
            return null;
        }

        $ids = $usuario->recintoIdsAsignados();

        return $ids === [] ? null : $ids;
    }

    public static function estaRestringido(?Authenticatable $usuario = null): bool
    {
        return self::recintoIdsDe($usuario ?? auth()->user()) !== null;
    }

    /**
     * Acota una consulta `DB::table()` que ya expone la columna de recinto del equipo (por ejemplo
     * `equipos.recinto_id` tras un join) — sin efecto si el usuario no tiene restricción.
     */
    public static function limitarConsulta(QueryBuilder $query, string $columnaRecinto): QueryBuilder
    {
        $ids = self::recintoIds();

        if ($ids !== null) {
            $query->whereIn($columnaRecinto, $ids);
        }

        return $query;
    }

    /**
     * Subconsulta con los ids de equipos de los recintos dados, sin pasar por el modelo `Equipo`
     * (evita aplicar su propio global scope dentro de otro).
     *
     * @param  array<int, int>  $recintoIds
     */
    public static function equiposDeRecintos(array $recintoIds): QueryBuilder
    {
        return DB::table('equipos')->select('id')->whereIn('recinto_id', $recintoIds);
    }

    public static function verificarRecinto(?int $recintoId): void
    {
        $ids = self::recintoIds();

        if ($ids !== null && ! in_array($recintoId, $ids, true)) {
            throw RecintoFueraDeAlcanceException::paraRecinto();
        }
    }

    public static function verificarEquipo(?int $equipoId): void
    {
        $ids = self::recintoIds();

        if ($ids !== null && ! self::equiposDeRecintos($ids)->where('id', $equipoId)->exists()) {
            throw RecintoFueraDeAlcanceException::paraEquipo();
        }
    }
}
