<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * SPEC-ME-019 (RF-89): el registro de accesos solo se consulta. Ningún rol puede crearlo, editarlo
 * ni borrarlo desde la aplicación.
 */
class EventoAutenticacionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any.evento_autenticacion');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('view.evento_autenticacion');
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }
}
