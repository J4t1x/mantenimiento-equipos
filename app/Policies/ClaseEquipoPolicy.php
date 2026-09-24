<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ClaseEquipo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ClaseEquipoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any.clase_equipo');
    }

    public function view(AuthUser $authUser, ClaseEquipo $claseEquipo): bool
    {
        return $authUser->can('view.clase_equipo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create.clase_equipo');
    }

    public function update(AuthUser $authUser, ClaseEquipo $claseEquipo): bool
    {
        return $authUser->can('update.clase_equipo');
    }

    public function delete(AuthUser $authUser, ClaseEquipo $claseEquipo): bool
    {
        return $authUser->can('delete.clase_equipo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any.clase_equipo');
    }

    public function restore(AuthUser $authUser, ClaseEquipo $claseEquipo): bool
    {
        return $authUser->can('restore.clase_equipo');
    }

    public function forceDelete(AuthUser $authUser, ClaseEquipo $claseEquipo): bool
    {
        return $authUser->can('force_delete.clase_equipo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any.clase_equipo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any.clase_equipo');
    }

    public function replicate(AuthUser $authUser, ClaseEquipo $claseEquipo): bool
    {
        return $authUser->can('replicate.clase_equipo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder.clase_equipo');
    }
}
