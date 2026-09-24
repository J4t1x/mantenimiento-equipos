<?php

namespace App\Filament\Resources\PlanMantenimientos\Pages;

use App\Filament\Resources\PlanMantenimientos\PlanMantenimientoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlanMantenimiento extends ViewRecord
{
    protected static string $resource = PlanMantenimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
