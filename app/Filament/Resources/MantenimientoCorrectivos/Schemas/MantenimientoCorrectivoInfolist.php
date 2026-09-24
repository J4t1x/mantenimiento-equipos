<?php

namespace App\Filament\Resources\MantenimientoCorrectivos\Schemas;

use App\Models\MantenimientoCorrectivo;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * RF-51 del SRS (Módulo 12): Mantenimiento correctivo no tenía forma de consultarse sin entrar a
 * modo edición.
 */
class MantenimientoCorrectivoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('equipo.nombre')
                    ->label('Equipo'),
                TextEntry::make('fecha')
                    ->label('Fecha de la falla')
                    ->date('d-m-Y'),
                TextEntry::make('falla_descripcion')
                    ->label('Descripción de la falla')
                    ->columnSpanFull(),
                TextEntry::make('costo')
                    ->label('Costo')
                    ->money('CLP'),
                TextEntry::make('tipo_gasto')
                    ->label('Tipo de gasto')
                    ->placeholder('—'),

                Section::make('Registro')
                    ->icon(Heroicon::OutlinedClock)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Creado')
                            ->dateTime('d-m-Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('updated_at')
                            ->label('Actualizado')
                            ->dateTime('d-m-Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('deleted_at')
                            ->label('Eliminado')
                            ->dateTime('d-m-Y H:i')
                            ->visible(fn (MantenimientoCorrectivo $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
