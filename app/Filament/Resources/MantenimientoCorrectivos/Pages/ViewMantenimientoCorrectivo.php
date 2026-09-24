<?php

namespace App\Filament\Resources\MantenimientoCorrectivos\Pages;

use App\Filament\Resources\MantenimientoCorrectivos\MantenimientoCorrectivoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMantenimientoCorrectivo extends ViewRecord
{
    protected static string $resource = MantenimientoCorrectivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
