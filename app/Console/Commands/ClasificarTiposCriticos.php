<?php

namespace App\Console\Commands;

use App\Enums\Criticidad;
use App\Enums\TipoEquipoCritico;
use App\Models\Equipo;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * RF-75 del SRS (Res. Ex. 1341/2017, "Definiciones"): clasificación inicial de los equipos en los
 * 6 tipos que la norma exige considerar críticos, a partir del nombre
 * ({@see TipoEquipoCritico::sugerirPorNombre()}).
 *
 * Por defecto solo muestra lo que haría. Con `--aplicar` asigna el tipo a los equipos sin
 * clasificar cuya criticidad ya es Crítico. **No cambia la criticidad de nadie**: un equipo que
 * por nombre corresponde a un tipo de la norma pero no está marcado Crítico (p. ej. las máquinas
 * de diálisis de la planilla 2026, marcadas Relevante) se lista como inconsistencia para que la
 * revise el encargado. La monitorización hemodinámica invasiva no se deduce del nombre y queda
 * para clasificar a mano.
 */
#[Signature('app:clasificar-tipos-criticos {--aplicar : Asignar el tipo sugerido a los equipos sin conflicto}')]
#[Description('Sugiere (y opcionalmente asigna) el tipo de equipo crítico de la norma MINSAL según el nombre del equipo.')]
class ClasificarTiposCriticos extends Command
{
    public function handle(): int
    {
        $aplicar = (bool) $this->option('aplicar');
        $asignados = [];
        $inconsistencias = [];
        $sinSugerencia = 0;

        Equipo::query()
            ->whereNull('tipo_critico_norma')
            ->with('recinto')
            ->orderBy('nombre')
            ->get()
            ->each(function (Equipo $equipo) use ($aplicar, &$asignados, &$inconsistencias, &$sinSugerencia): void {
                $tipo = TipoEquipoCritico::sugerirPorNombre($equipo->nombre);

                if ($tipo === null) {
                    $sinSugerencia++;

                    return;
                }

                if ($tipo->exigeCriticidadCritica() && $equipo->criticidad !== Criticidad::Critico) {
                    $inconsistencias[] = [
                        $equipo->recinto?->nombre,
                        $equipo->nombre,
                        $equipo->n_inventario,
                        $equipo->criticidad?->getLabel(),
                        $tipo->getLabel(),
                    ];

                    return;
                }

                if ($aplicar) {
                    $equipo->update(['tipo_critico_norma' => $tipo]);
                }

                $asignados[$tipo->getLabel()] = ($asignados[$tipo->getLabel()] ?? 0) + 1;
            });

        $this->info($aplicar ? 'Tipos asignados:' : 'Tipos que se asignarían (sin cambios; usa --aplicar):');
        $this->table(['Tipo de la norma', 'Equipos'], collect($asignados)->map(fn (int $cantidad, string $tipo): array => [$tipo, $cantidad])->values()->all());
        $this->line("Equipos sin clasificar y sin sugerencia por nombre: {$sinSugerencia} (incluye la monitorización hemodinámica invasiva, que se clasifica a mano).");

        if ($inconsistencias !== []) {
            $this->newLine();
            $this->warn(count($inconsistencias).' equipo(s) corresponden por nombre a un tipo crítico de la norma pero no están marcados "Crítico". No se modificaron: requieren revisión del encargado.');
            $this->table(['Recinto', 'Equipo', 'N° inventario', 'Criticidad actual', 'Tipo de la norma'], $inconsistencias);
        }

        return self::SUCCESS;
    }
}
