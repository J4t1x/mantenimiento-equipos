<?php

namespace App\Filament\Resources\Convenios\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * RF-39 del SRS: los campos del formulario de Convenio se agrupan en secciones temáticas —
 * Identificación y Vigencia y presupuesto — en vez de una lista plana sin jerarquía visual, mismo
 * criterio que RF-37 aplicó a `EquipoForm`. Ningún campo cambia de comportamiento: es
 * reordenamiento visual, no un cambio funcional.
 */
class ConvenioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->description('Proveedor y respaldo administrativo del convenio')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->columns(2)
                    ->schema([
                        Select::make('proveedor_id')
                            ->relationship('proveedor', 'nombre')
                            ->searchable()
                            ->preload()
                            ->label('Proveedor')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('nombre')
                            ->label('Nombre del convenio')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('n_resolucion')
                            ->label('N° de resolución / orden de compra')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Vigencia y presupuesto')
                    ->description('Período de vigencia, monto anual y estado del convenio')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(2)
                    ->schema([
                        DatePicker::make('fecha_resolucion')
                            ->label('Fecha de resolución')
                            ->required(),
                        DatePicker::make('fecha_expiracion')
                            ->label('Fecha de expiración')
                            ->required(),
                        TextInput::make('monto_anual')
                            ->label('Monto anual')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('subasignacion_sigfe')
                            ->label('Subasignación SIGFE')
                            ->helperText('Referencia manual, sin integración real (BRIEF, pregunta abierta 5).'),
                        Toggle::make('activo')
                            ->label('Activo')
                            ->default(true)
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
