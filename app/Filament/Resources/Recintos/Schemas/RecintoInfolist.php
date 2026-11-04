<?php

namespace App\Filament\Resources\Recintos\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * SPEC-ME-014: ficha del recinto. La consultan el encargado del establecimiento y la Jefatura, que
 * no pueden editar el catálogo de recintos. Debajo van el programa anual de MP y el gasto
 * programado (relation managers del recurso).
 */
class RecintoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Establecimiento')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('nombre')
                            ->label('Nombre'),
                        IconEntry::make('activo')
                            ->label('Activo')
                            ->boolean(),
                    ]),
                Section::make('Responsable de mantenimiento preventivo')
                    ->description('Profesional designado formalmente por la Subdirección Administrativa del establecimiento (Res. Ex. 1341/2017).')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('responsable_mp_nombre')
                            ->label('Nombre')
                            ->placeholder('Sin designar'),
                        TextEntry::make('responsable_mp_cargo')
                            ->label('Cargo')
                            ->placeholder('—'),
                        TextEntry::make('responsable_mp_documento')
                            ->label('N° de documento de designación')
                            ->placeholder('—'),
                        TextEntry::make('responsable_mp_fecha_designacion')
                            ->label('Fecha de designación')
                            ->date('d-m-Y')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
