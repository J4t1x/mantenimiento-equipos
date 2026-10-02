<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label('Correo verificado el'),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Al editar, deja este campo vacío para mantener la contraseña actual.'),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label('Roles'),
                // RF-63 (Módulo 15): sin recintos, el usuario ve todos (subdepartamento del SSA).
                Select::make('recintos')
                    ->relationship('recintos', 'nombre')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label('Recintos')
                    ->helperText('Deja vacío para el personal del subdepartamento, que ve todos los recintos. Con recintos asignados, el usuario solo ve y registra datos de esos recintos.'),
                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
            ]);
    }
}
