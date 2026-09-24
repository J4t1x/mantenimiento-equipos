<?php

namespace App\Filament\Resources\Convenios\Tables;

use App\Actions\CalcularEjecucionConvenioAction;
use App\Exports\ConveniosFiltradosExport;
use App\Filament\Support\AccionExportarTablaFiltrada;
use App\Models\Convenio;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ConveniosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->headerActions([
                AccionExportarTablaFiltrada::make(
                    'convenios.xlsx',
                    fn ($query) => new ConveniosFiltradosExport($query),
                ),
            ])
            ->columns([
                TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombre')
                    ->label('Convenio')
                    ->searchable(),
                TextColumn::make('n_resolucion')
                    ->label('N° resolución')
                    ->searchable(),
                TextColumn::make('fecha_resolucion')
                    ->label('Fecha resolución')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('fecha_expiracion')
                    ->label('Fecha expiración')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('monto_anual')
                    ->label('Monto anual')
                    ->money('CLP')
                    ->sortable(),
                TextColumn::make('subasignacion_sigfe')
                    ->label('SIGFE')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('ejecucion_convenio')
                    ->label('Ejecución '.now()->year)
                    ->state(function (Convenio $record): string {
                        $ejecucion = app(CalcularEjecucionConvenioAction::class)
                            ->paraConvenio($record, now()->year);

                        return $ejecucion['porcentaje'] === null ? 'Sin datos' : "{$ejecucion['porcentaje']}%";
                    })
                    ->badge()
                    ->color(function (Convenio $record): string {
                        $ejecucion = app(CalcularEjecucionConvenioAction::class)
                            ->paraConvenio($record, now()->year);

                        return $ejecucion['sobregirado'] ? 'danger' : 'success';
                    })
                    ->tooltip('RN-05 — % del monto anual ejecutado; en rojo si supera el monto anual'),
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
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // RF-41 (corrige RF-11 para Convenios): filtrar por proveedor y por vigencia,
                // además de la búsqueda por proveedor/nombre/n° de resolución ya existente.
                SelectFilter::make('proveedor_id')
                    ->relationship('proveedor', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Proveedor'),
                SelectFilter::make('vigencia')
                    ->label('Vigencia')
                    ->options([
                        'vigente' => 'Vigente',
                        'vencido' => 'Vencido',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'vigente' => $query->whereDate('fecha_expiracion', '>=', now()),
                            'vencido' => $query->whereDate('fecha_expiracion', '<', now()),
                            default => $query,
                        };
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon(Heroicon::OutlinedDocumentCheck)
            ->emptyStateHeading('Aún no hay convenios registrados')
            ->emptyStateDescription('Registra los convenios de mantenimiento vigentes con proveedores externos.')
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }
}
