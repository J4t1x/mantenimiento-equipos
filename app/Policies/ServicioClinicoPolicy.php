<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ServicioClinico;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ServicioClinicoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any.servicio_clinico');
    }

    public function view(AuthUser $authUser, ServicioClinico $servicioClinico): bool
    {
        return $authUser->can('view.servicio_clinico');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create.servicio_clinico');
    }

    public function update(AuthUser $authUser, ServicioClinico $servicioClinico): bool
    {
        return $authUser->can('update.servicio_clinico');
    }

    public function delete(AuthUser $authUser, ServicioClinico $servicioClinico): bool
    {
        return $authUser->can('delete.servicio_clinico');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any.servicio_clinico');
    }

    public function restore(AuthUser $authUser, ServicioClinico $servicioClinico): bool
    {
        return $authUser->can('restore.servicio_clinico');
    }

    public function forceDelete(AuthUser $authUser, ServicioClinico $servicioClinico): bool
    {
        return $authUser->can('force_delete.servicio_clinico');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any.servicio_clinico');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any.servicio_clinico');
    }

    public function replicate(AuthUser $authUser, ServicioClinico $servicioClinico): bool
    {
        return $authUser->can('replicate.servicio_clinico');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder.servicio_clinico');
    }
}
