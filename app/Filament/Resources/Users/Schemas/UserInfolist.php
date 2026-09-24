<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nombre'),
                TextEntry::make('email')
                    ->label('Correo electrónico'),
                TextEntry::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->placeholder('Sin rol asignado'),
                IconEntry::make('activo')
                    ->label('Activo')
                    ->boolean(),
                TextEntry::make('email_verified_at')
                    ->label('Correo verificado')
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('—'),
                TextEntry::make('created_at')
                    ->label('Creado')
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('—'),
                TextEntry::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('—'),
            ]);
    }
}
