<?php

namespace App\Filament\Resources\Recintos\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

/**
 * RF-76 (Módulo 17): gasto programado anual de MP y MC del recinto, uno por año. La carga de la
 * planilla lo toma de su cabecera ("GASTO PROGRAMADO MP/MC"). Solo lo edita quien puede editar
 * el recinto (personal del subdepartamento, RF-63).
 */
class PresupuestosMantenimientoRelationManager extends RelationManager
{
    protected static string $relationship = 'presupuestosMantenimiento';

    protected static ?string $title = 'Gasto programado anual';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('anio')
                    ->label('Año')
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->default(now()->year)
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('recinto_id', $this->getOwnerRecord()->getKey())),
                TextInput::make('gasto_programado_mp')
                    ->label('Gasto programado MP')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$'),
                TextInput::make('gasto_programado_mc')
                    ->label('Gasto programado MC')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('anio')
            ->defaultSort('anio', 'desc')
            ->columns([
                TextColumn::make('anio')
                    ->label('Año'),
                TextColumn::make('gasto_programado_mp')
                    ->label('Gasto programado MP')
                    ->money('CLP')
                    ->placeholder('—'),
                TextColumn::make('gasto_programado_mc')
                    ->label('Gasto programado MC')
                    ->money('CLP')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
