<?php

namespace App\Filament\Resources\PlanMantenimientos\Pages;

use App\Filament\Resources\PlanMantenimientos\PlanMantenimientoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlanMantenimientos extends ListRecords
{
    protected static string $resource = PlanMantenimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
