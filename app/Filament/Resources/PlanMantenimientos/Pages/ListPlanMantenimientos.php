<?php

namespace App\Filament\Resources\PlanMantenimientos\Pages;

use App\Filament\Resources\PlanMantenimientos\PlanMantenimientoResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListPlanMantenimientos extends ListRecords
{
    protected static string $resource = PlanMantenimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // SPEC-ME-023: vista de calendario de las mantenciones, alternativa a este listado.
            Action::make('calendario')
                ->label('Ver calendario')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('gray')
                ->url(PlanMantenimientoResource::getUrl('calendario')),
            CreateAction::make(),
        ];
    }
}
