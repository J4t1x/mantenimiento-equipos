<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Convenio;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ConvenioPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any.convenio');
    }

    public function view(AuthUser $authUser, Convenio $convenio): bool
    {
        return $authUser->can('view.convenio');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create.convenio');
    }

    public function update(AuthUser $authUser, Convenio $convenio): bool
    {
        return $authUser->can('update.convenio');
    }

    public function delete(AuthUser $authUser, Convenio $convenio): bool
    {
        return $authUser->can('delete.convenio');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any.convenio');
    }

    public function restore(AuthUser $authUser, Convenio $convenio): bool
    {
        return $authUser->can('restore.convenio');
    }

    public function forceDelete(AuthUser $authUser, Convenio $convenio): bool
    {
        return $authUser->can('force_delete.convenio');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any.convenio');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any.convenio');
    }

    public function replicate(AuthUser $authUser, Convenio $convenio): bool
    {
        return $authUser->can('replicate.convenio');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder.convenio');
    }
}
