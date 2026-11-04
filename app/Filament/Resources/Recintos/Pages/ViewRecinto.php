<?php

namespace App\Filament\Resources\Recintos\Pages;

use App\Filament\Resources\Recintos\RecintoResource;
use App\Filament\Resources\Recintos\Schemas\RecintoForm;
use App\Models\Recinto;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

/**
 * SPEC-ME-014: ficha del recinto. El encargado del establecimiento (con el recinto asignado) no
 * administra el catálogo, pero desde aquí registra el responsable de MP (acción "Editar
 * responsable de MP") y, en las secciones de abajo, el programa anual y el gasto programado
 * (`RecintoPolicy::gestionarDatosMp()`). La Jefatura la ve en solo lectura.
 */
class ViewRecinto extends ViewRecord
{
    protected static string $resource = RecintoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            $this->editarResponsableMpAction(),
        ];
    }

    private function editarResponsableMpAction(): Action
    {
        return Action::make('editarResponsableMp')
            ->label('Editar responsable de MP')
            ->icon(Heroicon::OutlinedUserCircle)
            ->color('gray')
            ->modalHeading('Responsable de mantenimiento preventivo')
            ->modalSubmitActionLabel('Guardar')
            ->schema(RecintoForm::camposResponsableMp())
            ->fillForm(fn (Recinto $record): array => $record->only([
                'responsable_mp_nombre',
                'responsable_mp_cargo',
                'responsable_mp_documento',
                'responsable_mp_fecha_designacion',
            ]))
            ->action(function (Recinto $record, array $data): void {
                $record->update($data);

                Notification::make()->title('Responsable de MP actualizado')->success()->send();
                $this->refreshFormData(array_keys($data));
            })
            ->visible(fn (Recinto $record): bool => Gate::allows('gestionarDatosMp', $record))
            ->authorize(fn (Recinto $record): bool => Gate::allows('gestionarDatosMp', $record));
    }
}
