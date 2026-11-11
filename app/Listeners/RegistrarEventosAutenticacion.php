<?php

namespace App\Listeners;

use App\Enums\EventoAutenticacion as TipoEvento;
use App\Models\EventoAutenticacion;
use App\Models\User;
use App\Support\BloqueoInicioSesion;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;

/**
 * SPEC-ME-019 (RF-89, AUDIT §3.1): registra los eventos de autenticación de Laravel. Los bloqueos y
 * las solicitudes de recuperación los registran las páginas de Filament (`App\Filament\Pages\Auth`),
 * y los cambios de contraseña, `App\Observers\UserObserver`.
 */
class RegistrarEventosAutenticacion
{
    public function __construct(private readonly BloqueoInicioSesion $bloqueo) {}

    public function ingreso(Login $evento): void
    {
        EventoAutenticacion::registrar(
            TipoEvento::IngresoExitoso,
            $evento->user instanceof User ? $evento->user : null,
            detalle: $evento->remember ? ['recordarme' => true] : [],
        );
    }

    public function fallo(Failed $evento): void
    {
        $usuario = $evento->user instanceof User ? $evento->user : null;

        EventoAutenticacion::registrar(
            TipoEvento::IngresoFallido,
            $usuario,
            $evento->credentials['email'] ?? null,
            ['motivo' => $usuario === null ? 'cuenta_inexistente' : ($usuario->activo ? 'contrasena_incorrecta' : 'cuenta_inactiva')],
        );
    }

    public function cierre(Logout $evento): void
    {
        if ($evento->user instanceof User) {
            EventoAutenticacion::registrar(TipoEvento::CierreSesion, $evento->user);
        }
    }

    public function recuperacion(PasswordReset $evento): void
    {
        if ($evento->user instanceof User) {
            EventoAutenticacion::registrar(TipoEvento::RecuperacionContrasena, $evento->user);
            $this->bloqueo->desbloquearCuenta($evento->user->email);
        }
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'ingreso',
            Failed::class => 'fallo',
            Logout::class => 'cierre',
            PasswordReset::class => 'recuperacion',
        ];
    }
}
