<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * RF-75 del SRS (Res. Ex. 1341/2017 del MINSAL, "Definiciones"): equipos que la norma obliga a
 * considerar críticos como mínimo, más "No corresponde" para el resto. Un equipo sin valor
 * todavía no se ha clasificado.
 */
enum TipoEquipoCritico: string implements HasLabel
{
    case MonitorizacionHemodinamicaInvasiva = 'monitorizacion_hemodinamica_invasiva';
    case MonitorDesfibrilador = 'monitor_desfibrilador';
    case VentiladorMecanico = 'ventilador_mecanico';
    case Incubadora = 'incubadora';
    case MaquinaDialisis = 'maquina_dialisis';
    case MaquinaAnestesia = 'maquina_anestesia';
    case NoCorresponde = 'no_corresponde';

    public function getLabel(): string
    {
        return match ($this) {
            self::MonitorizacionHemodinamicaInvasiva => 'Monitorización hemodinámica invasiva',
            self::MonitorDesfibrilador => 'Monitor desfibrilador',
            self::VentiladorMecanico => 'Ventilador mecánico (fijo o de transporte)',
            self::Incubadora => 'Incubadora',
            self::MaquinaDialisis => 'Máquina de diálisis',
            self::MaquinaAnestesia => 'Máquina de anestesia',
            self::NoCorresponde => 'No corresponde a un tipo de la norma',
        };
    }

    /**
     * true para los 6 tipos que la norma exige considerar críticos.
     */
    public function exigeCriticidadCritica(): bool
    {
        return $this !== self::NoCorresponde;
    }

    /**
     * Sugerencia a partir del nombre del equipo, para la clasificación inicial (comando
     * `app:clasificar-tipos-criticos`). La monitorización hemodinámica invasiva no se deduce del
     * nombre (un "monitor multiparámetros" puede tenerla o no) y queda para clasificar a mano.
     */
    public static function sugerirPorNombre(string $nombre): ?self
    {
        $nombre = mb_strtolower(trim($nombre));
        $nombre = strtr($nombre, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);

        return match (true) {
            str_contains($nombre, 'desfibrilador') => self::MonitorDesfibrilador,
            str_contains($nombre, 'ventilador') => self::VentiladorMecanico,
            str_contains($nombre, 'incubadora') => self::Incubadora,
            str_contains($nombre, 'anestesia') => self::MaquinaAnestesia,
            str_contains($nombre, 'dialisis') && ! str_contains($nombre, 'planta') => self::MaquinaDialisis,
            default => null,
        };
    }
}
