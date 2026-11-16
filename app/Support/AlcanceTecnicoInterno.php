<?php

namespace App\Support;

use App\Models\PlanMantenimiento;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * RF-20 del SRS: un técnico interno solo puede registrar la ejecución de los equipos que tiene
 * asignados como responsable (`PlanMantenimiento.responsable_interno_id`). El resto de los roles
 * (Encargado de Mantención, Jefatura, `super_admin`) no tiene esta restricción — RF-20 solo la
 * pide para el rol Técnico Interno (la parte de proveedor externo queda fuera, sujeta a la
 * pregunta abierta 7 del BRIEF, sin acceso propio al sistema todavía).
 *
 * Reutilizada desde el recurso de Bitácora y desde el relation manager de Planificación MP, y
 * desde la guarda de modelo en `EjecucionMensual` (defensa en profundidad, igual que RN-02).
 */
class AlcanceTecnicoInterno
{
    private const ROL = 'Técnico Interno';

    public static function usuarioActualEsTecnicoInterno(): bool
    {
        $usuario = auth()->user();

        return $usuario instanceof User && $usuario->hasRole(self::ROL);
    }

    /**
     * true si el usuario actual puede gestionar (crear/editar) la ejecución mensual del plan
     * dado — siempre true para roles distintos de Técnico Interno.
     */
    public static function puedeGestionarPlan(?PlanMantenimiento $plan): bool
    {
        if (! self::usuarioActualEsTecnicoInterno()) {
            return true;
        }

        return $plan !== null && $plan->responsable_interno_id === auth()->id();
    }

    /**
     * SPEC-ME-013: true si el usuario actual puede registrar la bitácora del plan dado. Exige el
     * permiso de edición de la bitácora (Jefatura y Encargado de Convenios solo la ven) y, para el
     * técnico interno, que sea el responsable del plan (RF-20). Lo usan la grilla del plan y la
     * Bitácora anual.
     */
    public static function puedeRegistrarEnPlan(?PlanMantenimiento $plan): bool
    {
        return (bool) auth()->user()?->can('update.ejecucion_mensual') && self::puedeGestionarPlan($plan);
    }

    /**
     * SPEC-ME-022: true si el usuario puede registrar o editar correctivos del equipo dado. Para
     * el técnico interno exige ser responsable interno de algún plan del equipo (misma regla que
     * RF-20 para la bitácora); siempre true para el resto de los roles.
     */
    public static function puedeGestionarEquipo(?int $equipoId, ?User $usuario = null): bool
    {
        $usuario ??= auth()->user();

        if (! $usuario instanceof User || ! $usuario->hasRole(self::ROL)) {
            return true;
        }

        return $equipoId !== null && PlanMantenimiento::withoutGlobalScopes()
            ->where('equipo_id', $equipoId)
            ->where('responsable_interno_id', $usuario->getKey())
            ->exists();
    }

    /**
     * SPEC-ME-022: restringe el Select de "Equipo" del formulario de correctivos a los equipos
     * asignados al técnico interno actual — sin efecto para el resto de los roles.
     */
    public static function limitarEquipos(Builder $query): Builder
    {
        if (self::usuarioActualEsTecnicoInterno()) {
            $query->whereHas('planesMantenimiento', fn (Builder $planes): Builder => $planes->where('responsable_interno_id', auth()->id()));
        }

        return $query;
    }

    /**
     * Restringe el Select de "Plan de mantenimiento" del formulario de Bitácora a los planes
     * asignados al técnico interno actual — sin efecto para el resto de los roles.
     */
    public static function limitarPlanes(Builder $query): Builder
    {
        if (self::usuarioActualEsTecnicoInterno()) {
            $query->where('responsable_interno_id', auth()->id());
        }

        return $query;
    }
}
