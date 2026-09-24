<?php

namespace App\Filament\Resources\MantenimientoCorrectivos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MantenimientoCorrectivoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('equipo_id')
                    ->relationship('equipo', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Equipo')
                    ->required(),
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
                    ->label('Tipo de gasto')
                    ->helperText('Texto libre por ahora (BRIEF, pregunta abierta 6).'),
            ]);
    }
}
