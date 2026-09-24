<?php

namespace App\Filament\Resources\Convenios\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EjecucionesMensualesRelationManager extends RelationManager
{
    protected static string $relationship = 'ejecucionesMensuales';

    protected static ?string $title = 'Ejecución mensual del gasto';

    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('anio')
                    ->label('Año')
                    ->numeric()
                    ->required(),
                Select::make('mes')
                    ->label('Mes')
                    ->options(self::MESES)
                    ->required(),
                TextInput::make('n_orden_compra')
                    ->label('N° orden de compra / factura')
                    ->required(),
                TextInput::make('monto')
                    ->label('Monto')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('n_orden_compra')
            ->columns([
                TextColumn::make('anio')
                    ->label('Año')
                    ->sortable(),
                TextColumn::make('mes')
                    ->label('Mes')
                    ->formatStateUsing(fn (int $state) => self::MESES[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('n_orden_compra')
                    ->label('N° orden de compra / factura'),
                TextColumn::make('monto')
                    ->label('Monto')
                    ->money('CLP'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
