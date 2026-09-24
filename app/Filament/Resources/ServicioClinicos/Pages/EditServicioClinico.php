<?php

namespace App\Filament\Resources\ServicioClinicos\Pages;

use App\Filament\Resources\ServicioClinicos\ServicioClinicoResource;
use App\Filament\Support\AccionesEliminarProtegidas;
use Filament\Resources\Pages\EditRecord;

class EditServicioClinico extends EditRecord
{
    protected static string $resource = ServicioClinicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesEliminarProtegidas::individual(),
        ];
    }
}
