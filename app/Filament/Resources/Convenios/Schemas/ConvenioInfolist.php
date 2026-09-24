<?php

namespace App\Filament\Resources\Convenios\Schemas;

use App\Actions\CalcularEjecucionConvenioAction;
use App\Models\Convenio;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ConvenioInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('proveedor.nombre')
                    ->label('Proveedor'),
                TextEntry::make('nombre')
                    ->label('Convenio'),
                TextEntry::make('n_resolucion')
                    ->label('N° resolución'),
                TextEntry::make('fecha_resolucion')
                    ->label('Fecha resolución')
                    ->date('d-m-Y'),
                TextEntry::make('fecha_expiracion')
                    ->label('Fecha expiración')
                    ->date('d-m-Y'),
                TextEntry::make('monto_anual')
                    ->label('Monto anual')
                    ->money('CLP'),
                TextEntry::make('subasignacion_sigfe')
                    ->label('SIGFE')
                    ->placeholder('—'),
                IconEntry::make('activo')
                    ->label('Activo')
                    ->boolean(),
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
                    ->visible(fn (Convenio $record): bool => $record->trashed()),

                Section::make('Ejecución presupuestaria')
                    ->description('RN-05 — monto ejecutado / monto anual del año en curso')
                    ->schema([
                        TextEntry::make('ejecucion_convenio')
                            ->label('Ejecución '.now()->year)
                            ->state(function (Convenio $record): string {
                                $ejecucion = app(CalcularEjecucionConvenioAction::class)
                                    ->paraConvenio($record, now()->year);

                                if ($ejecucion['porcentaje'] === null) {
                                    return 'Sin monto anual definido';
                                }

                                $ejecutado = number_format($ejecucion['monto_ejecutado'], 0, ',', '.');
                                $anual = number_format($ejecucion['monto_anual'], 0, ',', '.');

                                return "{$ejecucion['porcentaje']}% (\${$ejecutado} de \${$anual})";
                            })
                            ->color(function (Convenio $record): string {
                                $ejecucion = app(CalcularEjecucionConvenioAction::class)
                                    ->paraConvenio($record, now()->year);

                                return $ejecucion['sobregirado'] ? 'danger' : 'success';
                            }),
                        TextEntry::make('alerta_sobregiro')
                            ->label('')
                            ->state('⚠ El monto ejecutado supera el monto anual del convenio.')
                            ->color('danger')
                            ->visible(function (Convenio $record): bool {
                                $ejecucion = app(CalcularEjecucionConvenioAction::class)
                                    ->paraConvenio($record, now()->year);

                                return $ejecucion['sobregirado'];
                            }),
                    ]),
            ]);
    }
}
