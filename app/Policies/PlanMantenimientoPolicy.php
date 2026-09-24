<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PlanMantenimiento;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PlanMantenimientoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any.plan_mantenimiento');
    }

    public function view(AuthUser $authUser, PlanMantenimiento $planMantenimiento): bool
    {
        return $authUser->can('view.plan_mantenimiento');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create.plan_mantenimiento');
    }

    public function update(AuthUser $authUser, PlanMantenimiento $planMantenimiento): bool
    {
        return $authUser->can('update.plan_mantenimiento');
    }

    public function delete(AuthUser $authUser, PlanMantenimiento $planMantenimiento): bool
    {
        return $authUser->can('delete.plan_mantenimiento');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any.plan_mantenimiento');
    }

    public function restore(AuthUser $authUser, PlanMantenimiento $planMantenimiento): bool
    {
        return $authUser->can('restore.plan_mantenimiento');
    }

    public function forceDelete(AuthUser $authUser, PlanMantenimiento $planMantenimiento): bool
    {
        return $authUser->can('force_delete.plan_mantenimiento');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any.plan_mantenimiento');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any.plan_mantenimiento');
    }

    public function replicate(AuthUser $authUser, PlanMantenimiento $planMantenimiento): bool
    {
        return $authUser->can('replicate.plan_mantenimiento');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder.plan_mantenimiento');
    }
}
