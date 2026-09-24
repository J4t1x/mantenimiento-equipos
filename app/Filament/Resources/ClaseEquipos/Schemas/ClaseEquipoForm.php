<?php

namespace App\Filament\Resources\ClaseEquipos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ClaseEquipoForm
{
    /**
     * `clase_padre_id` en blanco = es una clase; con valor = es una subclase de esa clase
     * (MODELO-DATOS §3.3).
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required(),
                Select::make('clase_padre_id')
                    ->relationship(
                        'clasePadre',
                        'nombre',
                        fn ($query) => $query->whereNull('clase_padre_id'),
                    )
                    ->searchable()
                    ->preload()
                    ->label('Clase padre')
                    ->helperText('Déjalo vacío si este registro es una clase; selecciónalo si es una subclase.'),
                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
            ]);
    }
}
