<?php

/**
 * SPEC-ME-019: parámetros de seguridad del inicio de sesión (RF-87 y RF-88 del SRS).
 * Los valores por defecto siguen ASVS 5.0 V6 y pueden ajustarse por `.env` sin tocar el código.
 */
return [

    'contrasena' => [
        // Largo mínimo de toda contraseña: formulario de Usuarios, recuperación y app:crear-usuario.
        'largo_minimo' => (int) env('SEGURIDAD_CONTRASENA_LARGO_MINIMO', 12),
    ],

    'bloqueo' => [
        // Por cuenta (correo escrito, exista o no): N fallos dentro de la ventana la bloquean.
        'cuenta' => [
            'intentos' => (int) env('SEGURIDAD_BLOQUEO_CUENTA_INTENTOS', 5),
            'ventana_minutos' => (int) env('SEGURIDAD_BLOQUEO_CUENTA_VENTANA', 15),
            'minutos' => (int) env('SEGURIDAD_BLOQUEO_CUENTA_MINUTOS', 15),
        ],

        // Por dirección IP, contando los fallos de todas las cuentas.
        'ip' => [
            'intentos' => (int) env('SEGURIDAD_BLOQUEO_IP_INTENTOS', 20),
            'ventana_minutos' => (int) env('SEGURIDAD_BLOQUEO_IP_VENTANA', 15),
            'minutos' => (int) env('SEGURIDAD_BLOQUEO_IP_MINUTOS', 15),
        ],
    ],

];
