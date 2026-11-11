<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * SPEC-ME-019 (RF-88): correo de recuperación de contraseña, en español. El enlace lo arma la
 * página de Filament; vence en `auth.passwords.users.expire` minutos y sirve una sola vez.
 *
 * No es encolable (la de Filament sí): el sistema no tiene un worker de colas, y el envío ya se
 * hace después de responder, con `defer`. Encolada, con `QUEUE_CONNECTION=database` el correo
 * quedaría esperando en `jobs` sin enviarse nunca.
 */
class RecuperarContrasena extends ResetPassword
{
    public string $url;

    protected function resetUrl($notifiable): string
    {
        return $this->url;
    }

    public function toMail($notifiable): MailMessage
    {
        $minutos = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Recuperación de contraseña — '.config('app.name'))
            ->greeting('Estimado(a) '.$notifiable->name.':')
            ->line('Recibimos una solicitud para crear una contraseña nueva para su cuenta en la Bitácora de Mantenimiento de Equipos.')
            ->action('Crear contraseña nueva', $this->resetUrl($notifiable))
            ->line("El enlace vence en {$minutos} minutos y sirve una sola vez.")
            ->line('Si usted no hizo esta solicitud, ignore este correo: su contraseña actual sigue vigente. Si recibe correos como este sin haberlos pedido, avise al administrador del sistema.')
            ->salutation('Servicio de Salud Aysén');
    }
}
