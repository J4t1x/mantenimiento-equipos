<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * SPEC-ME-019 (RF-87, ASVS 5.0 V6): bloqueo del inicio de sesión por intentos fallidos, por cuenta
 * y por dirección IP.
 *
 * - Los fallos se cuentan en una ventana (`seguridad.bloqueo.*.ventana_minutos`). Al llegar al
 *   máximo, se bloquea durante un tiempo fijo (`seguridad.bloqueo.*.minutos`) y el contador vuelve
 *   a cero.
 * - La cuenta se identifica por el correo escrito, exista o no, para no revelar qué cuentas existen.
 * - Las claves usan un hash del correo: el caché no guarda datos personales en claro.
 */
class BloqueoInicioSesion
{
    public const CUENTA = 'cuenta';

    public const IP = 'ip';

    /**
     * Segundos que faltan para que termine el bloqueo de la cuenta o de la IP (el mayor), o 0.
     */
    public function segundosRestantes(?string $email, ?string $ip): int
    {
        return max(
            $this->segundosRestantesDe($this->clave(self::CUENTA, $email)),
            $this->segundosRestantesDe($this->clave(self::IP, $ip)),
        );
    }

    /**
     * Cuenta un fallo para la cuenta y la IP. Devuelve los bloqueos que se activaron con este fallo
     * (`cuenta`, `ip` o ambos), para registrarlos y avisar al usuario.
     *
     * @return array<int, string>
     */
    public function registrarFallo(?string $email, ?string $ip): array
    {
        $activados = [];

        foreach ([self::CUENTA => $email, self::IP => $ip] as $tipo => $valor) {
            $clave = $this->clave($tipo, $valor);

            if ($clave === null) {
                continue;
            }

            $fallos = RateLimiter::hit("{$clave}:fallos", $this->config($tipo, 'ventana_minutos') * 60);

            if ($fallos >= $this->config($tipo, 'intentos')) {
                $minutos = $this->config($tipo, 'minutos');
                Cache::put("{$clave}:hasta", now()->addMinutes($minutos)->getTimestamp(), now()->addMinutes($minutos));
                RateLimiter::clear("{$clave}:fallos");
                $activados[] = $tipo;
            }
        }

        return $activados;
    }

    /**
     * Ingreso correcto: los fallos previos de la cuenta dejan de contar. Los de la IP se conservan,
     * para que un ingreso válido no reinicie un ataque repartido entre varias cuentas.
     */
    public function limpiarFallosDeCuenta(?string $email): void
    {
        if ($clave = $this->clave(self::CUENTA, $email)) {
            RateLimiter::clear("{$clave}:fallos");
        }
    }

    /**
     * Recuperación de contraseña: quien controla el correo puede volver a ingresar de inmediato.
     */
    public function desbloquearCuenta(?string $email): void
    {
        if ($clave = $this->clave(self::CUENTA, $email)) {
            RateLimiter::clear("{$clave}:fallos");
            Cache::forget("{$clave}:hasta");
        }
    }

    public function config(string $tipo, string $parametro): int
    {
        return max(1, (int) config("seguridad.bloqueo.{$tipo}.{$parametro}"));
    }

    private function segundosRestantesDe(?string $clave): int
    {
        if ($clave === null) {
            return 0;
        }

        $hasta = Cache::get("{$clave}:hasta");

        return $hasta === null ? 0 : max(0, (int) $hasta - now()->getTimestamp());
    }

    private function clave(string $tipo, ?string $valor): ?string
    {
        $valor = mb_strtolower(trim((string) $valor));

        return $valor === '' ? null : "bloqueo-inicio-sesion:{$tipo}:".hash('sha256', $valor);
    }
}
