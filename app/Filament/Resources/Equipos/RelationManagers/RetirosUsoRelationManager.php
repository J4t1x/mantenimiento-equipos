<?php

namespace App\Filament\Resources\Equipos\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * RF-73 (Módulo 16, Res. Ex. 1341/2017 §7.4.iii): historial de retiros de uso del equipo, de solo
 * lectura. El retiro y el reingreso se registran con las acciones de la ficha del equipo
 * (`ViewEquipo`), que además cambian su estado activo.
 */
class RetirosUsoRelationManager extends RelationManager
{
    protected static string $relationship = 'retirosUso';

    protected static ?string $title = 'Retiros de uso';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_retiro', 'desc')
            ->columns([
                TextColumn::make('fecha_retiro')
                    ->label('Retiro')
                    ->date('d-m-Y'),
                TextColumn::make('motivo')
                    ->label('Motivo')
                    ->limit(60)
                    ->tooltip(fn ($record): string => $record->motivo),
                TextColumn::make('documento_retiro')
                    ->label('Evidencia'),
                TextColumn::make('fecha_reingreso')
                    ->label('Reingreso')
                    ->date('d-m-Y')
                    ->placeholder('Sigue retirado')
                    ->color(fn ($record): ?string => $record->fecha_reingreso === null ? 'danger' : null),
                TextColumn::make('documento_reingreso')
                    ->label('Documento de reingreso')
                    ->placeholder('—'),
                TextColumn::make('registradoPor.name')
                    ->label('Registrado por')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading('Sin retiros de uso registrados');
    }
}
