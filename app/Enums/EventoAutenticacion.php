<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * SPEC-ME-019 (RF-89): eventos de autenticación que se registran en `eventos_autenticacion`.
 */
enum EventoAutenticacion: string implements HasColor, HasLabel
{
    case IngresoExitoso = 'ingreso_exitoso';
    case IngresoFallido = 'ingreso_fallido';
    case Bloqueo = 'bloqueo';
    case CierreSesion = 'cierre_sesion';
    case SolicitudRecuperacion = 'solicitud_recuperacion';
    case RecuperacionContrasena = 'recuperacion_contrasena';
    case CambioContrasena = 'cambio_contrasena';

    public function getLabel(): string
    {
        return match ($this) {
            self::IngresoExitoso => 'Ingreso exitoso',
            self::IngresoFallido => 'Ingreso fallido',
            self::Bloqueo => 'Bloqueo',
            self::CierreSesion => 'Cierre de sesión',
            self::SolicitudRecuperacion => 'Solicitud de recuperación',
            self::RecuperacionContrasena => 'Recuperación de contraseña',
            self::CambioContrasena => 'Cambio de contraseña',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::IngresoExitoso => 'success',
            self::IngresoFallido => 'warning',
            self::Bloqueo => 'danger',
            self::CierreSesion => 'gray',
            self::SolicitudRecuperacion, self::RecuperacionContrasena, self::CambioContrasena => 'info',
        };
    }
}
