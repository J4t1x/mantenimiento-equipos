<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MantenimientoCorrectivo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MantenimientoCorrectivoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any.mantenimiento_correctivo');
    }

    public function view(AuthUser $authUser, MantenimientoCorrectivo $mantenimientoCorrectivo): bool
    {
        return $authUser->can('view.mantenimiento_correctivo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create.mantenimiento_correctivo');
    }

    public function update(AuthUser $authUser, MantenimientoCorrectivo $mantenimientoCorrectivo): bool
    {
        return $authUser->can('update.mantenimiento_correctivo');
    }

    public function delete(AuthUser $authUser, MantenimientoCorrectivo $mantenimientoCorrectivo): bool
    {
        return $authUser->can('delete.mantenimiento_correctivo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any.mantenimiento_correctivo');
    }

    public function restore(AuthUser $authUser, MantenimientoCorrectivo $mantenimientoCorrectivo): bool
    {
        return $authUser->can('restore.mantenimiento_correctivo');
    }

    public function forceDelete(AuthUser $authUser, MantenimientoCorrectivo $mantenimientoCorrectivo): bool
    {
        return $authUser->can('force_delete.mantenimiento_correctivo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any.mantenimiento_correctivo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any.mantenimiento_correctivo');
    }

    public function replicate(AuthUser $authUser, MantenimientoCorrectivo $mantenimientoCorrectivo): bool
    {
        return $authUser->can('replicate.mantenimiento_correctivo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder.mantenimiento_correctivo');
    }
}
