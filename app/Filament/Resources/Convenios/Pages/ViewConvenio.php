<?php

namespace App\Filament\Resources\Convenios\Pages;

use App\Filament\Resources\Convenios\ConvenioResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewConvenio extends ViewRecord
{
    protected static string $resource = ConvenioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
