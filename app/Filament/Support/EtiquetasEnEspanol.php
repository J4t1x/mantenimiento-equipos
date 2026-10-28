<?php

namespace App\Filament\Support;

use Illuminate\Support\Str;

/**
 * SPEC-ME-010: Filament aplica mayúscula a cada palabra de la etiqueta del modelo ("Crear Plan De
 * Mantenimiento"), una convención del inglés. En español solo va con mayúscula la primera letra:
 * los títulos quedan "Crear plan de mantenimiento" y las migas, el menú y los listados "Planes de
 * mantenimiento".
 */
trait EtiquetasEnEspanol
{
    public static function getTitleCaseModelLabel(): string
    {
        return static::getModelLabel();
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return Str::ucfirst(static::getPluralModelLabel());
    }
}
