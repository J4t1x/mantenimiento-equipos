<?php

namespace App\Filament\Resources\ClaseEquipos\Pages;

use App\Filament\Resources\ClaseEquipos\ClaseEquipoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClaseEquipos extends ListRecords
{
    protected static string $resource = ClaseEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
