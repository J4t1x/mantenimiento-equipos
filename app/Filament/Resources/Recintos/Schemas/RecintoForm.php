<?php

namespace App\Filament\Resources\Recintos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * RF-70 (Módulo 16, Res. Ex. 1341/2017 §7.1): además del nombre, cada recinto registra al
 * profesional responsable de la MP designado formalmente por la Subdirección Administrativa.
 */
class RecintoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required(),
                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
                Section::make('Responsable de mantenimiento preventivo')
                    ->description('Profesional designado formalmente por la Subdirección Administrativa del establecimiento (Res. Ex. 1341/2017).')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('responsable_mp_nombre')
                            ->label('Nombre')
                            ->maxLength(150),
                        TextInput::make('responsable_mp_cargo')
                            ->label('Cargo')
                            ->maxLength(150),
                        TextInput::make('responsable_mp_documento')
                            ->label('N° de documento de designación')
                            ->placeholder('Ej.: Res. Ex. N° 1234')
                            ->maxLength(100),
                        DatePicker::make('responsable_mp_fecha_designacion')
                            ->label('Fecha de designación'),
                    ]),
            ]);
    }
}
