<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Proveedor;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProveedorPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any.proveedor');
    }

    public function view(AuthUser $authUser, Proveedor $proveedor): bool
    {
        return $authUser->can('view.proveedor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create.proveedor');
    }

    public function update(AuthUser $authUser, Proveedor $proveedor): bool
    {
        return $authUser->can('update.proveedor');
    }

    public function delete(AuthUser $authUser, Proveedor $proveedor): bool
    {
        return $authUser->can('delete.proveedor');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any.proveedor');
    }

    public function restore(AuthUser $authUser, Proveedor $proveedor): bool
    {
        return $authUser->can('restore.proveedor');
    }

    public function forceDelete(AuthUser $authUser, Proveedor $proveedor): bool
    {
        return $authUser->can('force_delete.proveedor');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any.proveedor');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any.proveedor');
    }

    public function replicate(AuthUser $authUser, Proveedor $proveedor): bool
    {
        return $authUser->can('replicate.proveedor');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder.proveedor');
    }
}
