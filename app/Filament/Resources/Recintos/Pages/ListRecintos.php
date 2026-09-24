<?php

namespace App\Filament\Resources\Recintos\Pages;

use App\Filament\Resources\Recintos\RecintoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecintos extends ListRecords
{
    protected static string $resource = RecintoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
