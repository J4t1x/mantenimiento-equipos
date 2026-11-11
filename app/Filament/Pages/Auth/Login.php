<?php

namespace App\Filament\Pages\Auth;

use App\Enums\EventoAutenticacion as TipoEvento;
use App\Models\EventoAutenticacion;
use App\Models\User;
use App\Support\BloqueoInicioSesion;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as LoginBase;
use Filament\Support\Components\Attributes\Exposed;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

/**
 * SPEC-ME-019 (RF-87): inicio de sesión con bloqueo por intentos fallidos, por cuenta y por IP.
 *
 * Se mantiene el límite propio de Filament (5 intentos por minuto por IP) como freno de ráfagas.
 * Durante un bloqueo se rechaza incluso la contraseña correcta, y el mensaje es el mismo exista o
 * no la cuenta.
 */
class Login extends LoginBase
{
    /** Bloqueos activados por el último fallo, para mostrar su mensaje en vez del genérico. */
    private int $segundosDeBloqueoActivado = 0;

    #[Exposed]
    public function authenticate(): ?LoginResponse
    {
        $email = $this->emailEscrito();
        $bloqueo = app(BloqueoInicioSesion::class);

        if (($segundos = $bloqueo->segundosRestantes($email, request()->ip())) > 0) {
            EventoAutenticacion::registrar(TipoEvento::IngresoFallido, email: $email, detalle: ['motivo' => 'acceso_bloqueado']);

            $this->lanzarMensajeDeBloqueo($segundos);
        }

        $respuesta = parent::authenticate();

        if ($respuesta !== null) {
            $bloqueo->limpiarFallosDeCuenta($email);
        }

        return $respuesta;
    }

    protected function fireFailedEvent(Guard $guard, ?Authenticatable $user, #[SensitiveParameter] array $credentials): void
    {
        parent::fireFailedEvent($guard, $user, $credentials);

        $email = $credentials['email'] ?? null;
        $bloqueo = app(BloqueoInicioSesion::class);

        foreach ($bloqueo->registrarFallo($email, request()->ip()) as $tipo) {
            EventoAutenticacion::registrar(
                TipoEvento::Bloqueo,
                $user instanceof User ? $user : null,
                $email,
                ['tipo' => $tipo, 'minutos' => $bloqueo->config($tipo, 'minutos')],
            );
        }

        $this->segundosDeBloqueoActivado = $bloqueo->segundosRestantes($email, request()->ip());
    }

    protected function throwFailureValidationException(): never
    {
        if ($this->segundosDeBloqueoActivado > 0) {
            $this->lanzarMensajeDeBloqueo($this->segundosDeBloqueoActivado);
        }

        parent::throwFailureValidationException();
    }

    private function lanzarMensajeDeBloqueo(int $segundos): never
    {
        $minutos = max(1, (int) ceil($segundos / 60));

        throw ValidationException::withMessages([
            'data.email' => trans_choice(
                'Por seguridad, el acceso quedó bloqueado temporalmente por demasiados intentos fallidos. Intente nuevamente en :minutos minuto o use «¿Ha olvidado su contraseña?».|Por seguridad, el acceso quedó bloqueado temporalmente por demasiados intentos fallidos. Intente nuevamente en :minutos minutos o use «¿Ha olvidado su contraseña?».',
                $minutos,
                ['minutos' => $minutos],
            ),
        ]);
    }

    private function emailEscrito(): string
    {
        return mb_strtolower(trim((string) ($this->data['email'] ?? '')));
    }
}
