<?php

namespace App\Filament\Resources\Recintos\Pages;

use App\Filament\Resources\Recintos\RecintoResource;
use App\Filament\Support\AccionesEliminarProtegidas;
use Filament\Resources\Pages\EditRecord;

class EditRecinto extends EditRecord
{
    protected static string $resource = RecintoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesEliminarProtegidas::individual(),
        ];
    }
}
