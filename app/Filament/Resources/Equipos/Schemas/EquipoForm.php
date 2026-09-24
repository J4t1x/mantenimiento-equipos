<?php

namespace App\Filament\Resources\Equipos\Schemas;

use App\Enums\Criticidad;
use App\Enums\EstadoEquipo;
use App\Enums\Propiedad;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * RF-37 del SRS: los ~20 campos de la ficha de un equipo se agrupan en 5 secciones temáticas
 * (Identificación, Ubicación y clasificación, Ficha técnica, Estado y garantía, Plan de
 * mantenimiento) en vez de una lista plana sin jerarquía visual — mismo criterio de agrupación
 * que `EquipoInfolist`, para que formulario y ficha se lean igual. Ningún campo cambia de
 * comportamiento (mismas validaciones, mismos valores por defecto): es reordenamiento visual, no
 * un cambio funcional.
 */
class EquipoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->description('Nombre y datos de fábrica que distinguen a este equipo')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(2)
                    ->schema([
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('marca')
                            ->label('Marca'),
                        TextInput::make('modelo')
                            ->label('Modelo'),
                        TextInput::make('serie')
                            ->label('N° de serie'),
                        TextInput::make('n_inventario')
                            ->label('N° de inventario')
                            ->required(),
                    ]),

                Section::make('Ubicación y clasificación')
                    ->description('Dónde está el equipo y a qué catálogo pertenece')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->columns(2)
                    ->schema([
                        Select::make('recinto_id')
                            ->relationship('recinto', 'nombre')
                            ->searchable()
                            ->preload()
                            ->label('Recinto')
                            ->required(),
                        Select::make('servicio_clinico_id')
                            ->relationship('servicioClinico', 'nombre')
                            ->searchable()
                            ->preload()
                            ->label('Servicio clínico')
                            ->required(),
                        Select::make('clase_id')
                            ->relationship('clase', 'nombre', fn ($query) => $query->whereNull('clase_padre_id'))
                            ->searchable()
                            ->preload()
                            ->label('Clase')
                            ->required(),
                        Select::make('subclase_id')
                            ->relationship('subclase', 'nombre', fn ($query) => $query->whereNotNull('clase_padre_id'))
                            ->searchable()
                            ->preload()
                            ->label('Subclase'),
                    ]),

                Section::make('Ficha técnica')
                    ->description('Antigüedad, vida útil y régimen de propiedad')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->columns(3)
                    ->schema([
                        TextInput::make('anio_adquisicion')
                            ->label('Año de adquisición')
                            ->numeric(),
                        TextInput::make('vida_util_anios')
                            ->label('Vida útil (años)')
                            ->numeric(),
                        Select::make('propiedad')
                            ->label('Propiedad')
                            ->options(Propiedad::class)
                            ->required(),
                    ]),

                Section::make('Estado y garantía')
                    ->description('Condición actual, criticidad clínica y cobertura de garantía')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columns(2)
                    ->schema([
                        Select::make('estado')
                            ->label('Estado')
                            ->options(EstadoEquipo::class)
                            ->default('sin_evaluar')
                            ->required(),
                        Select::make('criticidad')
                            ->label('Criticidad')
                            ->options(Criticidad::class)
                            ->required(),
                        Toggle::make('en_garantia')
                            ->label('En garantía')
                            ->default(false)
                            ->required()
                            ->live(),
                        TextInput::make('garantia_anio_vencimiento')
                            ->label('Año de vencimiento de garantía')
                            ->numeric()
                            ->visible(fn ($get) => (bool) $get('en_garantia')),
                        Toggle::make('activo')
                            ->label('Activo')
                            ->default(true)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Plan de mantenimiento')
                    ->description('Si el equipo está bajo un plan de mantenimiento preventivo')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->columns(2)
                    ->schema([
                        Toggle::make('bajo_plan_mp')
                            ->label('Bajo plan de mantenimiento preventivo')
                            ->default(false)
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('anio_ingreso_plan')
                            ->label('Año de ingreso al plan')
                            ->numeric()
                            ->visible(fn ($get) => (bool) $get('bajo_plan_mp')),
                    ]),
            ]);
    }
}
