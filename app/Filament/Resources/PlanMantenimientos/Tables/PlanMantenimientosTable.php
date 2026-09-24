<?php

namespace App\Filament\Resources\PlanMantenimientos\Tables;

use App\Enums\TipoMantenimiento;
use App\Models\PlanMantenimiento;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PlanMantenimientosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('equipo.nombre')
                    ->label('Equipo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('anio')
                    ->label('Año')
                    ->sortable(),
                TextColumn::make('frecuencia_anual')
                    ->label('Frecuencia anual')
                    ->badge(),
                TextColumn::make('tipo_mantenimiento')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->placeholder('—'),
                TextColumn::make('responsableInterno.name')
                    ->label('Responsable interno')
                    ->placeholder('—'),
                TextColumn::make('convenio.nombre')
                    ->label('Convenio')
                    ->placeholder('—'),
                TextColumn::make('costo_anual_referencia')
                    ->label('Costo anual ref.')
                    ->money('CLP')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('anio')
                    ->label('Año')
                    ->options(fn () => PlanMantenimiento::query()->distinct()->orderByDesc('anio')->pluck('anio', 'anio')),
                // RF-41 (corrige RF-11 para Planificación MP): filtrar por tipo de mantenimiento.
                SelectFilter::make('tipo_mantenimiento')
                    ->label('Tipo de mantenimiento')
                    ->options(TipoMantenimiento::class),
                // RF-50 (Módulo 12): soft-delete, mismo criterio que Equipo/Convenio/Correctivo.
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
