<?php

namespace App\Filament\Resources\MantenimientoCorrectivos\Schemas;

use App\Support\AlcanceTecnicoInterno;
use App\Support\RangosDeValores;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class MantenimientoCorrectivoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('equipo_id')
                    ->relationship('equipo', 'nombre', modifyQueryUsing: fn (Builder $query): Builder => AlcanceTecnicoInterno::limitarEquipos($query))
                    ->searchable()
                    ->preload()
                    ->label('Equipo')
                    ->required(),
                DatePicker::make('fecha')
                    ->label('Fecha de la falla')
                    ->maxDate(now())
                    ->validationMessages(['before_or_equal' => RangosDeValores::MENSAJE_FECHA_FUTURA])
                    ->required(),
                Textarea::make('falla_descripcion')
                    ->label('Descripción de la falla')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('costo')
                    ->label('Costo')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->required(),
                TextInput::make('tipo_gasto')
                    ->label('Tipo de gasto')
                    ->helperText('Texto libre por ahora (BRIEF, pregunta abierta 6).'),
            ]);
    }
}
