<?php

namespace App\Filament\Resources\Recintos\Tables;

use App\Filament\Support\AccionesEliminarProtegidas;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecintosTable
{
    public const FILTRO_PROGRAMA_SIN_VALIDAR = 'programa_sin_validar';

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable(),
                // RF-70 (Res. Ex. 1341/2017 §7.1).
                TextColumn::make('responsable_mp_nombre')
                    ->label('Responsable MP')
                    ->description(fn ($record): ?string => $record->responsable_mp_cargo)
                    ->searchable()
                    ->placeholder('Sin designar'),
                TextColumn::make('responsable_mp_documento')
                    ->label('Designación')
                    ->description(fn ($record): ?string => $record->responsable_mp_fecha_designacion?->format('d-m-Y'))
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // RF-72 (Res. Ex. 1341/2017 §7.3): enlazado desde la alerta del Escritorio.
                Filter::make(self::FILTRO_PROGRAMA_SIN_VALIDAR)
                    ->label('Sin programa anual validado (año en curso)')
                    ->query(fn (Builder $query): Builder => $query->sinProgramaValidado()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    AccionesEliminarProtegidas::enLote(),
                ]),
            ])
            ->emptyStateIcon(Heroicon::OutlinedBuildingOffice2)
            ->emptyStateHeading('Aún no hay recintos registrados')
            ->emptyStateDescription('Registra los establecimientos del Servicio de Salud Aysén (hospitales, CESFAM, postas) para empezar a asociar equipos.')
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }
}
