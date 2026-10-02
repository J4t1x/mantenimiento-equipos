<?php

namespace App\Filament\Resources\Equipos\Pages;

use App\Actions\RegistrarRetiroUsoAction;
use App\Filament\Resources\Equipos\EquipoResource;
use App\Models\Equipo;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * RF-73 (Módulo 16, Res. Ex. 1341/2017 §7.4.iii): la ficha del equipo permite registrar su retiro
 * de uso con la evidencia escrita que exige la norma, y su reingreso. Ambas acciones requieren
 * permiso para editar el equipo.
 */
class ViewEquipo extends ViewRecord
{
    protected static string $resource = EquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            $this->retirarDeUsoAction(),
            $this->registrarReingresoAction(),
        ];
    }

    private function retirarDeUsoAction(): Action
    {
        return Action::make('retirarDeUso')
            ->label('Retirar de uso')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->modalDescription('Deja el equipo inactivo y registra la evidencia escrita del retiro (Res. Ex. 1341/2017).')
            ->schema([
                DatePicker::make('fecha')
                    ->label('Fecha de retiro')
                    ->default(now())
                    ->maxDate(now())
                    ->required(),
                Textarea::make('motivo')
                    ->label('Motivo')
                    ->placeholder('Ej.: MP reprogramada de junio no realizada dentro de 30 días')
                    ->required(),
                TextInput::make('documento')
                    ->label('N° de documento de evidencia')
                    ->placeholder('Ej.: Memo N° 45')
                    ->maxLength(100)
                    ->required(),
            ])
            ->action(function (Equipo $record, array $data): void {
                app(RegistrarRetiroUsoAction::class)->retirar($record, CarbonImmutable::parse($data['fecha']), $data['motivo'], $data['documento']);

                Notification::make()->title('Equipo retirado de uso')->success()->send();
                $this->refreshFormData(['activo']);
            })
            ->visible(fn (Equipo $record): bool => $record->activo && EquipoResource::canEdit($record) && $record->retiroUsoAbierto() === null);
    }

    private function registrarReingresoAction(): Action
    {
        return Action::make('registrarReingreso')
            ->label('Registrar reingreso')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('success')
            ->modalDescription('Cierra el retiro de uso abierto y vuelve a dejar el equipo activo.')
            ->schema([
                DatePicker::make('fecha')
                    ->label('Fecha de reingreso')
                    ->default(now())
                    ->maxDate(now())
                    ->required(),
                TextInput::make('documento')
                    ->label('N° de documento de reingreso')
                    ->maxLength(100)
                    ->required(),
            ])
            ->action(function (Equipo $record, array $data): void {
                app(RegistrarRetiroUsoAction::class)->reingresar($record, CarbonImmutable::parse($data['fecha']), $data['documento']);

                Notification::make()->title('Reingreso registrado')->success()->send();
                $this->refreshFormData(['activo']);
            })
            ->visible(fn (Equipo $record): bool => EquipoResource::canEdit($record) && $record->retiroUsoAbierto() !== null);
    }
}
