<?php

namespace App\Models\Concerns;

use App\Exceptions\ValorFueraDeRangoException;
use Illuminate\Support\Carbon;

/**
 * SPEC-ME-015 (FR-2): reglas de rango que valen para cualquier vía de escritura, no solo para los
 * formularios. El modelo declara qué montos no pueden ser negativos y qué fechas de hechos ya
 * ocurridos no pueden ser futuras, con la etiqueta que verá el usuario en el mensaje.
 */
trait ProtegeRangosDeValores
{
    /**
     * @return array<string, string> columna => etiqueta
     */
    protected function montosNoNegativos(): array
    {
        return [];
    }

    /**
     * @return array<string, string> columna => etiqueta
     */
    protected function fechasNoFuturas(): array
    {
        return [];
    }

    protected static function bootProtegeRangosDeValores(): void
    {
        static::saving(function (self $modelo): void {
            foreach ($modelo->montosNoNegativos() as $columna => $etiqueta) {
                $valor = $modelo->getAttribute($columna);

                if ($modelo->isDirty($columna) && $valor !== null && (float) $valor < 0) {
                    throw ValorFueraDeRangoException::montoNegativo($etiqueta);
                }
            }

            foreach ($modelo->fechasNoFuturas() as $columna => $etiqueta) {
                $valor = $modelo->getAttribute($columna);

                if ($modelo->isDirty($columna) && $valor !== null && Carbon::parse($valor)->startOfDay()->isAfter(today())) {
                    throw ValorFueraDeRangoException::fechaFutura($etiqueta);
                }
            }
        });
    }
}
