<?php

namespace App\Filament\Resources\PlanMantenimientos\Pages;

use App\Filament\Resources\PlanMantenimientos\PlanMantenimientoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlanMantenimiento extends EditRecord
{
    protected static string $resource = PlanMantenimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
