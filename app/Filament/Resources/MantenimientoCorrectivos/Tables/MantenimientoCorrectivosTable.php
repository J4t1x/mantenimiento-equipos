<?php

namespace App\Filament\Resources\MantenimientoCorrectivos\Tables;

use App\Exports\MantenimientoCorrectivosFiltradosExport;
use App\Filament\Support\AccionExportarTablaFiltrada;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MantenimientoCorrectivosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha')
            ->headerActions([
                AccionExportarTablaFiltrada::make(
                    'mantenimiento-correctivo.xlsx',
                    fn ($query) => new MantenimientoCorrectivosFiltradosExport($query),
                ),
            ])
            ->columns([
                TextColumn::make('equipo.nombre')
                    ->label('Equipo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('falla_descripcion')
                    ->label('Falla')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('costo')
                    ->label('Costo')
                    ->money('CLP')
                    ->sortable(),
                TextColumn::make('tipo_gasto')
                    ->label('Tipo de gasto')
                    ->placeholder('—'),
            ])
            ->filters([
                // RF-48 (Módulo 12): filtrar por equipo y por rango de fecha, además del estado
                // de soft-delete que ya existía.
                SelectFilter::make('equipo_id')
                    ->label('Equipo')
                    ->relationship('equipo', 'nombre')
                    ->searchable()
                    ->preload(),
                Filter::make('fecha')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, $desde) => $q->whereDate('fecha', '>=', $desde))
                        ->when($data['hasta'] ?? null, fn (Builder $q, $hasta) => $q->whereDate('fecha', '<=', $hasta))),
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
