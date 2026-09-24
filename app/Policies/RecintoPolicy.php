<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Recinto;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

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
        return $authUser->can('create.recinto');
    }

    public function update(AuthUser $authUser, Recinto $recinto): bool
    {
        return $authUser->can('update.recinto');
    }

    public function delete(AuthUser $authUser, Recinto $recinto): bool
    {
        return $authUser->can('delete.recinto');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any.recinto');
    }

    public function restore(AuthUser $authUser, Recinto $recinto): bool
    {
        return $authUser->can('restore.recinto');
    }

    public function forceDelete(AuthUser $authUser, Recinto $recinto): bool
    {
        return $authUser->can('force_delete.recinto');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any.recinto');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any.recinto');
    }

    public function replicate(AuthUser $authUser, Recinto $recinto): bool
    {
        return $authUser->can('replicate.recinto');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder.recinto');
    }
}
