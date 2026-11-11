<?php

namespace App\Observers;

use App\Enums\EventoAutenticacion as TipoEvento;
use App\Models\EventoAutenticacion;
use App\Models\User;

/**
 * SPEC-ME-019 (RF-89): registra los cambios de contraseña hechos con sesión iniciada (un
 * administrador desde Usuarios, o el propio usuario). La recuperación por correo se hace sin
 * sesión y queda registrada como "Recuperación de contraseña".
 */
class UserObserver
{
    public function updated(User $user): void
    {
        $actor = auth()->user();

        if (! $user->wasChanged('password') || ! ($actor instanceof User)) {
            return;
        }

        EventoAutenticacion::registrar(TipoEvento::CambioContrasena, $user, detalle: [
            'realizado_por' => $actor->is($user) ? 'el propio usuario' : "{$actor->name} <{$actor->email}>",
            'realizado_por_id' => $actor->getKey(),
        ]);
    }
}
