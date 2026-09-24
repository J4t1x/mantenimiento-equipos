<?php

namespace App\Filament\Resources\Proveedores\Tables;

use App\Filament\Support\AccionesEliminarProtegidas;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProveedoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('rut')
                    ->label('RUT')
                    ->searchable(),
                TextColumn::make('contacto')
                    ->label('Contacto')
                    ->toggleable(),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                // RF-55 (Módulo 12): columnas de auditoría uniformes con Recintos/Servicios clínicos.
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
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
            ->emptyStateIcon(Heroicon::OutlinedBriefcase)
            ->emptyStateHeading('Aún no hay proveedores registrados')
            ->emptyStateDescription('Registra los proveedores externos de mantención para poder asociarlos a convenios y planes de MP.')
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }
}
