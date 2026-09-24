<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Equipo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EquipoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any.equipo');
    }

    public function view(AuthUser $authUser, Equipo $equipo): bool
    {
        return $authUser->can('view.equipo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create.equipo');
    }

    public function update(AuthUser $authUser, Equipo $equipo): bool
    {
        return $authUser->can('update.equipo');
    }

    public function delete(AuthUser $authUser, Equipo $equipo): bool
    {
        return $authUser->can('delete.equipo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any.equipo');
    }

    public function restore(AuthUser $authUser, Equipo $equipo): bool
    {
        return $authUser->can('restore.equipo');
    }

    public function forceDelete(AuthUser $authUser, Equipo $equipo): bool
    {
        return $authUser->can('force_delete.equipo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any.equipo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any.equipo');
    }

    public function replicate(AuthUser $authUser, Equipo $equipo): bool
    {
        return $authUser->can('replicate.equipo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder.equipo');
    }
}
