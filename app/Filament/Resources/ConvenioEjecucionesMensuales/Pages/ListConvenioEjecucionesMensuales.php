<?php

namespace App\Filament\Resources\ConvenioEjecucionesMensuales\Pages;

use App\Filament\Resources\ConvenioEjecucionesMensuales\ConvenioEjecucionMensualResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConvenioEjecucionesMensuales extends ListRecords
{
    protected static string $resource = ConvenioEjecucionMensualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
