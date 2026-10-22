<?php

namespace App\Filament\Resources\ServicioClinicos\Tables;

use App\Filament\Support\AccionesEliminarProtegidas;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServicioClinicosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                // RF-84: equipos del catastro asociados al servicio. El conteo respeta el alcance
                // por recinto del usuario (global scope de `Equipo`, RF-63).
                TextColumn::make('equipos_count')
                    ->label('Equipos asociados')
                    ->counts('equipos')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    AccionesEliminarProtegidas::enLote(),
                ]),
            ])
            ->emptyStateIcon(Heroicon::OutlinedTag)
            ->emptyStateHeading('Aún no hay servicios clínicos registrados')
            ->emptyStateDescription('Registra el catálogo normalizado de servicios clínicos para reemplazar las variantes de texto libre de la planilla.')
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }
}
