<?php

namespace App\Filament\Resources\ClaseEquipos\Pages;

use App\Filament\Resources\ClaseEquipos\ClaseEquipoResource;
use App\Filament\Support\AccionesEliminarProtegidas;
use Filament\Resources\Pages\EditRecord;

class EditClaseEquipo extends EditRecord
{
    protected static string $resource = ClaseEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesEliminarProtegidas::individual(),
        ];
    }
}
