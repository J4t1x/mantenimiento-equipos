<?php

namespace App\Filament\Resources\ServicioClinicos\Pages;

use App\Filament\Resources\ServicioClinicos\ServicioClinicoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServicioClinicos extends ListRecords
{
    protected static string $resource = ServicioClinicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
