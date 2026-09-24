<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EjecucionMensual;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EjecucionMensualPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any.ejecucion_mensual');
    }

    public function view(AuthUser $authUser, EjecucionMensual $ejecucionMensual): bool
    {
        return $authUser->can('view.ejecucion_mensual');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create.ejecucion_mensual');
    }

    public function update(AuthUser $authUser, EjecucionMensual $ejecucionMensual): bool
    {
        return $authUser->can('update.ejecucion_mensual');
    }

    public function delete(AuthUser $authUser, EjecucionMensual $ejecucionMensual): bool
    {
        return $authUser->can('delete.ejecucion_mensual');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any.ejecucion_mensual');
    }

    public function restore(AuthUser $authUser, EjecucionMensual $ejecucionMensual): bool
    {
        return $authUser->can('restore.ejecucion_mensual');
    }

    public function forceDelete(AuthUser $authUser, EjecucionMensual $ejecucionMensual): bool
    {
        return $authUser->can('force_delete.ejecucion_mensual');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any.ejecucion_mensual');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any.ejecucion_mensual');
    }

    public function replicate(AuthUser $authUser, EjecucionMensual $ejecucionMensual): bool
    {
        return $authUser->can('replicate.ejecucion_mensual');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder.ejecucion_mensual');
    }
}
