<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ConvenioEjecucionMensual;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ConvenioEjecucionMensualPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any.convenio_ejecucion_mensual');
    }

    public function view(AuthUser $authUser, ConvenioEjecucionMensual $convenioEjecucionMensual): bool
    {
        return $authUser->can('view.convenio_ejecucion_mensual');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create.convenio_ejecucion_mensual');
    }

    public function update(AuthUser $authUser, ConvenioEjecucionMensual $convenioEjecucionMensual): bool
    {
        return $authUser->can('update.convenio_ejecucion_mensual');
    }

    public function delete(AuthUser $authUser, ConvenioEjecucionMensual $convenioEjecucionMensual): bool
    {
        return $authUser->can('delete.convenio_ejecucion_mensual');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any.convenio_ejecucion_mensual');
    }

    public function restore(AuthUser $authUser, ConvenioEjecucionMensual $convenioEjecucionMensual): bool
    {
        return $authUser->can('restore.convenio_ejecucion_mensual');
    }

    public function forceDelete(AuthUser $authUser, ConvenioEjecucionMensual $convenioEjecucionMensual): bool
    {
        return $authUser->can('force_delete.convenio_ejecucion_mensual');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any.convenio_ejecucion_mensual');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any.convenio_ejecucion_mensual');
    }

    public function replicate(AuthUser $authUser, ConvenioEjecucionMensual $convenioEjecucionMensual): bool
    {
        return $authUser->can('replicate.convenio_ejecucion_mensual');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder.convenio_ejecucion_mensual');
    }
}
