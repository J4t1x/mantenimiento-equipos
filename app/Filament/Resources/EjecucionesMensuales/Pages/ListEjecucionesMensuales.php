<?php

namespace App\Filament\Resources\EjecucionesMensuales\Pages;

use App\Filament\Resources\EjecucionesMensuales\EjecucionMensualResource;
use Filament\Resources\Pages\ListRecords;

class ListEjecucionesMensuales extends ListRecords
{
    protected static string $resource = EjecucionMensualResource::class;

    /**
     * RF-49 del SRS (Módulo 12): sin acción de crear — este listado es una vista global filtrable
     * (RF-48) de la bitácora, de solo consulta/edición; crear o eliminar un mes suelto rompía la
     * invariante de RN-01 (un plan siempre tiene exactamente 12 meses), que sí protege el grid de
     * `PlanMantenimientos\RelationManagers\EjecucionesMensualesRelationManager` — el único punto de
     * entrada donde corresponde crear/eliminar es a través de la frecuencia anual del plan.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
