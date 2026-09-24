<?php

namespace App\Filament\Resources\EjecucionesMensuales\Pages;

use App\Filament\Resources\EjecucionesMensuales\EjecucionMensualResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEjecucionMensual extends EditRecord
{
    protected static string $resource = EjecucionMensualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
