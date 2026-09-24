<?php

namespace App\Filament\Resources\ConvenioEjecucionesMensuales\Pages;

use App\Filament\Resources\ConvenioEjecucionesMensuales\ConvenioEjecucionMensualResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConvenioEjecucionMensual extends EditRecord
{
    protected static string $resource = ConvenioEjecucionMensualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
