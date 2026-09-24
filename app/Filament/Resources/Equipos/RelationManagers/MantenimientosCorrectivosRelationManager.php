<?php

namespace App\Filament\Resources\Equipos\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MantenimientosCorrectivosRelationManager extends RelationManager
{
    protected static string $relationship = 'mantenimientosCorrectivos';

    protected static ?string $title = 'Mantenimiento correctivo';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('fecha')
                    ->label('Fecha de la falla')
                    ->required(),
                Textarea::make('falla_descripcion')
                    ->label('Descripción de la falla')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('costo')
                    ->label('Costo')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                TextInput::make('tipo_gasto')
                    ->label('Tipo de gasto'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('falla_descripcion')
            ->defaultSort('fecha')
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('falla_descripcion')
                    ->label('Falla')
                    ->limit(50),
                TextColumn::make('costo')
                    ->label('Costo')
                    ->money('CLP'),
                TextColumn::make('tipo_gasto')
                    ->label('Tipo de gasto')
                    ->placeholder('—'),
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
