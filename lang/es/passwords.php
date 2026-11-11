<?php

/**
 * SPEC-ME-019: mensajes del restablecimiento de contraseña. "sent", "user" y "throttled" no se
 * muestran: la solicitud siempre responde con el mismo mensaje, para no revelar si el correo existe.
 */
return [
    'reset' => 'Su contraseña fue actualizada. Ya puede iniciar sesión.',
    'sent' => 'Si el correo corresponde a una cuenta activa, recibirá un enlace para crear una contraseña nueva.',
    'throttled' => 'Espere un momento antes de volver a intentarlo.',
    'token' => 'El enlace no es válido o ya venció. Solicite uno nuevo desde «¿Ha olvidado su contraseña?».',
    'user' => 'El enlace no es válido o ya venció. Solicite uno nuevo desde «¿Ha olvidado su contraseña?».',
];
