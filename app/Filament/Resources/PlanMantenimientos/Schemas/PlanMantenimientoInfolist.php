<?php

namespace App\Filament\Resources\PlanMantenimientos\Schemas;

use App\Models\PlanMantenimiento;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * RF-51 del SRS (Módulo 12): Plan de mantenimiento no tenía forma de consultarse sin entrar a
 * modo edición — mismo criterio de secciones que `PlanMantenimientoForm` (RF-39).
 */
class PlanMantenimientoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Equipo y período')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('equipo.nombre')
                            ->label('Equipo')
                            ->columnSpanFull(),
                        TextEntry::make('anio')
                            ->label('Año'),
                        TextEntry::make('frecuencia_anual')
                            ->label('Frecuencia anual')
                            ->badge(),
                    ]),

                Section::make('Ejecución y costo')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('tipo_mantenimiento')
                            ->label('Tipo de mantenimiento')
                            ->badge()
                            ->columnSpanFull(),
                        TextEntry::make('proveedor.nombre')
                            ->label('Proveedor')
                            ->placeholder('—'),
                        TextEntry::make('responsableInterno.name')
                            ->label('Responsable interno')
                            ->placeholder('—'),
                        TextEntry::make('convenio.nombre')
                            ->label('Convenio asociado')
                            ->placeholder('—'),
                        TextEntry::make('costo_anual_referencia')
                            ->label('Costo anual de referencia')
                            ->money('CLP')
                            ->placeholder('—'),
                    ]),

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
                            ->visible(fn (PlanMantenimiento $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
