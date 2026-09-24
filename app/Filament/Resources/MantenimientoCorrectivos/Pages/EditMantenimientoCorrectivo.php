<?php

namespace App\Filament\Resources\MantenimientoCorrectivos\Pages;

use App\Filament\Resources\MantenimientoCorrectivos\MantenimientoCorrectivoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditMantenimientoCorrectivo extends EditRecord
{
    protected static string $resource = MantenimientoCorrectivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
