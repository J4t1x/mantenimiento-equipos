<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Recinto;
use App\Support\AlcanceRecinto;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Generada por Shield. RF-63: además del permiso, las acciones que modifican el catálogo exigen que
 * el usuario no tenga recintos asignados (personal del subdepartamento): un encargado de un
 * establecimiento no puede crear recintos que no vería ni editar el suyo.
 */
class RecintoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any.recinto');
    }

    public function view(AuthUser $authUser, Recinto $recinto): bool
    {
        return $authUser->can('view.recinto');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create.recinto') && $this->administraElCatalogo($authUser);
    }

    public function update(AuthUser $authUser, Recinto $recinto): bool
    {
        return $authUser->can('update.recinto') && $this->administraElCatalogo($authUser);
    }

    public function delete(AuthUser $authUser, Recinto $recinto): bool
    {
        return $authUser->can('delete.recinto') && $this->administraElCatalogo($authUser);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any.recinto') && $this->administraElCatalogo($authUser);
    }

    public function restore(AuthUser $authUser, Recinto $recinto): bool
    {
        return $authUser->can('restore.recinto') && $this->administraElCatalogo($authUser);
    }

    public function forceDelete(AuthUser $authUser, Recinto $recinto): bool
    {
        return $authUser->can('force_delete.recinto') && $this->administraElCatalogo($authUser);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any.recinto') && $this->administraElCatalogo($authUser);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any.recinto') && $this->administraElCatalogo($authUser);
    }

    public function replicate(AuthUser $authUser, Recinto $recinto): bool
    {
        return $authUser->can('replicate.recinto') && $this->administraElCatalogo($authUser);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder.recinto') && $this->administraElCatalogo($authUser);
    }

    private function administraElCatalogo(AuthUser $authUser): bool
    {
        return ! AlcanceRecinto::estaRestringido($authUser);
    }
}
