<?php

namespace App\Filament\Pages\Auth;

use App\Enums\EventoAutenticacion as TipoEvento;
use App\Models\EventoAutenticacion;
use App\Models\User;
use App\Notifications\RecuperarContrasena;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as RequestPasswordResetBase;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Components\Attributes\Exposed;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Password;
use SensitiveParameter;

use function Illuminate\Support\defer;

/**
 * SPEC-ME-019 (RF-88, ASVS 5.0 V6): solicitud de recuperación de contraseña que no revela si el
 * correo existe.
 *
 * - El mensaje es el mismo para un correo existente, uno inexistente, una cuenta inactiva o una
 *   solicitud repetida antes de un minuto (Filament mostraba "no encontramos un usuario…").
 * - El correo se envía después de responder (`defer`): con SMTP síncrono, el tiempo de respuesta
 *   delataría las cuentas existentes.
 * - El enlace vence en `auth.passwords.users.expire` minutos y sirve una sola vez.
 */
class RequestPasswordReset extends RequestPasswordResetBase
{
    #[Exposed]
    public function request(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data = $this->form->getState();
        $credenciales = $this->getCredentialsFromFormData($data);
        $panel = Filament::getCurrentOrDefaultPanel();

        $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            $credenciales,
            function (CanResetPassword $user, #[SensitiveParameter] string $token) use ($panel): void {
                if (! ($user instanceof User) || ! $user->canAccessPanel($panel)) {
                    return;
                }

                $notification = new RecuperarContrasena($token);
                $notification->url = Filament::getResetPasswordUrl($token, $user);

                defer(fn () => $user->notify($notification));
            },
        );

        EventoAutenticacion::registrar(
            TipoEvento::SolicitudRecuperacion,
            User::query()->where('email', $credenciales['email'])->first(),
            $credenciales['email'],
            ['resultado' => $status],
        );

        $this->getMensajeUnico()->send();

        $this->form->fill();
    }

    private function getMensajeUnico(): Notification
    {
        return Notification::make()
            ->title('Revise su correo')
            ->body('Si el correo corresponde a una cuenta activa del sistema, recibirá un enlace para crear una contraseña nueva. El enlace vence en '.config('auth.passwords.users.expire').' minutos y sirve una sola vez.')
            ->success();
    }
}
