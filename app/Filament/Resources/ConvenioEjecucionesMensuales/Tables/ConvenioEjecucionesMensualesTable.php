<?php

namespace App\Filament\Resources\ConvenioEjecucionesMensuales\Tables;

use App\Models\ConvenioEjecucionMensual;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ConvenioEjecucionesMensualesTable
{
    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('convenio.nombre')
                    ->label('Convenio')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('anio')
                    ->label('Año')
                    ->sortable(),
                TextColumn::make('mes')
                    ->label('Mes')
                    ->formatStateUsing(fn (int $state) => self::MESES[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('n_orden_compra')
                    ->label('N° orden de compra / factura')
                    ->searchable(),
                TextColumn::make('monto')
                    ->label('Monto')
                    ->money('CLP')
                    ->sortable(),
            ])
            ->filters([
                // RF-48 (Módulo 12): este listado no tenía ningún filtro, ni siquiera por convenio o año.
                SelectFilter::make('convenio_id')
                    ->label('Convenio')
                    ->relationship('convenio', 'nombre')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('anio')
                    ->label('Año')
                    ->options(fn () => ConvenioEjecucionMensual::query()->distinct()->orderByDesc('anio')->pluck('anio', 'anio')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
