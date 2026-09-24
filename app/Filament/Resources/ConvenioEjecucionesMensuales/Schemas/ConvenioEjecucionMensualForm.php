<?php

namespace App\Filament\Resources\ConvenioEjecucionesMensuales\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * RF-39 del SRS: los campos del formulario de Ejecución mensual de convenio se agrupan en
 * secciones temáticas — Período, Comprobante — en vez de una lista plana sin jerarquía visual,
 * mismo criterio que RF-37 aplicó a `EquipoForm`. Ningún campo cambia de comportamiento.
 */
class ConvenioEjecucionMensualForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Período')
                    ->description('Convenio, año y mes que cubre esta ejecución')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->columns(2)
                    ->schema([
                        Select::make('convenio_id')
                            ->relationship('convenio', 'nombre')
                            ->searchable()
                            ->preload()
                            ->label('Convenio')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('anio')
                            ->label('Año')
                            ->numeric()
                            ->required(),
                        Select::make('mes')
                            ->label('Mes')
                            ->options([
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                            ])
                            ->required(),
                    ]),

                Section::make('Comprobante')
                    ->description('Orden de compra o factura y el monto ejecutado')
                    ->icon(Heroicon::OutlinedReceiptPercent)
                    ->columns(2)
                    ->schema([
                        TextInput::make('n_orden_compra')
                            ->label('N° orden de compra / factura')
                            ->required(),
                        TextInput::make('monto')
                            ->label('Monto')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                    ]),
            ]);
    }
}
