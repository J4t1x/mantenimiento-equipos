<?php

namespace App\Actions;

use App\Exceptions\RetiroUsoInvalidoException;
use App\Models\Equipo;
use App\Models\RetiroUso;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * RF-73 del SRS (Res. Ex. 1341/2017 §7.4.iii): si la MP reprogramada no se pudo realizar, el
 * equipo se retira de uso y del servicio clínico, con evidencia escrita. Retirar deja el equipo
 * inactivo, con lo que sale de la alerta "Críticos a retirar de uso" (RF-66). Reingresar cierra el
 * retiro y lo vuelve a activar.
 */
class RegistrarRetiroUsoAction
{
    public function retirar(Equipo $equipo, CarbonInterface $fecha, string $motivo, string $documento): RetiroUso
    {
        if ($equipo->retiroUsoAbierto() !== null) {
            throw RetiroUsoInvalidoException::yaRetirado();
        }

        return DB::transaction(function () use ($equipo, $fecha, $motivo, $documento): RetiroUso {
            $retiro = $equipo->retirosUso()->create([
                'fecha_retiro' => $fecha->toDateString(),
                'motivo' => $motivo,
                'documento_retiro' => $documento,
                'registrado_por_id' => auth()->id(),
            ]);

            $equipo->update(['activo' => false]);

            return $retiro;
        });
    }

    public function reingresar(Equipo $equipo, CarbonInterface $fecha, string $documento): RetiroUso
    {
        $retiro = $equipo->retiroUsoAbierto() ?? throw RetiroUsoInvalidoException::sinRetiroAbierto();

        return DB::transaction(function () use ($equipo, $retiro, $fecha, $documento): RetiroUso {
            $retiro->update([
                'fecha_reingreso' => $fecha->toDateString(),
                'documento_reingreso' => $documento,
            ]);

            $equipo->update(['activo' => true]);

            return $retiro;
        });
    }
}
