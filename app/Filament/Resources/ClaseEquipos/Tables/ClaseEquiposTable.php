<?php

namespace App\Filament\Resources\ClaseEquipos\Tables;

use App\Filament\Support\AccionesEliminarProtegidas;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClaseEquiposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('clasePadre.nombre')
                    ->label('Clase padre')
                    ->placeholder('— (es una clase)'),
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
            ->filters([
                // RF-55 (Módulo 12): distinguir clase padre de subclase, hoy solo visible como
                // texto en la columna "Clase padre".
                TernaryFilter::make('es_clase_padre')
                    ->label('Jerarquía')
                    ->placeholder('Todas')
                    ->trueLabel('Solo clases padre')
                    ->falseLabel('Solo subclases')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('clase_padre_id'),
                        false: fn (Builder $query) => $query->whereNotNull('clase_padre_id'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    AccionesEliminarProtegidas::enLote(),
                ]),
            ])
            ->emptyStateIcon(Heroicon::OutlinedRectangleGroup)
            ->emptyStateHeading('Aún no hay clases de equipo registradas')
            ->emptyStateDescription('Registra las clases y subclases de equipo médico antes de comenzar a cargar el catastro.')
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }
}
