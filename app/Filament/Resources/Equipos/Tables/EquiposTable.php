<?php

namespace App\Filament\Resources\Equipos\Tables;

use App\Enums\Criticidad;
use App\Enums\EstadoEquipo;
use App\Exports\EquiposFiltradosExport;
use App\Filament\Support\AccionExportarTablaFiltrada;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EquiposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->headerActions([
                AccionExportarTablaFiltrada::make(
                    'equipos.xlsx',
                    fn ($query) => new EquiposFiltradosExport($query),
                ),
            ])
            ->columns([
                TextColumn::make('recinto.nombre')
                    ->label('Recinto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('servicioClinico.nombre')
                    ->label('Servicio clínico')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('clase.nombre')
                    ->label('Clase')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('subclase.nombre')
                    ->label('Subclase')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('marca')
                    ->label('Marca')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('modelo')
                    ->label('Modelo')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('serie')
                    ->label('N° de serie')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('n_inventario')
                    ->label('N° de inventario')
                    ->searchable(),
                TextColumn::make('anio_adquisicion')
                    ->label('Año adquisición')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('vida_util_anios')
                    ->label('Vida útil (años)')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('propiedad')
                    ->label('Propiedad')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('criticidad')
                    ->label('Criticidad')
                    ->badge(),
                IconColumn::make('en_garantia')
                    ->label('En garantía')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('garantia_anio_vencimiento')
                    ->label('Vence garantía')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('bajo_plan_mp')
                    ->label('Bajo plan MP')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('anio_ingreso_plan')
                    ->label('Año ingreso al plan')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->toggleable(),
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
                // RF-11: filtrar por recinto, servicio clínico, criticidad y estado (además de la
                // búsqueda por nombre/n° de inventario, ya cubierta por las columnas `searchable`).
                SelectFilter::make('recinto_id')
                    ->relationship('recinto', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Recinto'),
                SelectFilter::make('servicio_clinico_id')
                    ->relationship('servicioClinico', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Servicio clínico'),
                SelectFilter::make('criticidad')
                    ->options(Criticidad::class)
                    ->label('Criticidad'),
                SelectFilter::make('estado')
                    ->options(EstadoEquipo::class)
                    ->label('Estado'),
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
            ])
            ->emptyStateIcon(Heroicon::OutlinedCube)
            ->emptyStateHeading('Aún no hay equipos en el catastro')
            ->emptyStateDescription('Registra el primer equipo, o carga el catastro desde la planilla vigente cuando los datos estén disponibles.')
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }
}
