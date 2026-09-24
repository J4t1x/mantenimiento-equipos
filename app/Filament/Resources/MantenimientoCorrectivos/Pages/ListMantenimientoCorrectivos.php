<?php

namespace App\Filament\Resources\MantenimientoCorrectivos\Pages;

use App\Filament\Resources\MantenimientoCorrectivos\MantenimientoCorrectivoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMantenimientoCorrectivos extends ListRecords
{
    protected static string $resource = MantenimientoCorrectivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
