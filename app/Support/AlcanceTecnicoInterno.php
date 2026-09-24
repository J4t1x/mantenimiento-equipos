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
