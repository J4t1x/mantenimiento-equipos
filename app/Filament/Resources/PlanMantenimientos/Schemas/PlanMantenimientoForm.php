<?php

namespace App\Filament\Resources\PlanMantenimientos\Schemas;

use App\Enums\FrecuenciaAnual;
use App\Enums\TipoMantenimiento;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * RF-39 del SRS: los campos del formulario de Planificación MP se agrupan en secciones temáticas —
 * Equipo y período, Ejecución y costo — en vez de una lista plana sin jerarquía visual, mismo
 * criterio que RF-37 aplicó a `EquipoForm`.
 *
 * Corrección 16-09-2026 (bug preexistente, detectado al verificar RF-39): la visibilidad
 * condicional de proveedor_id/responsable_interno_id comparaba `$get('tipo_mantenimiento')` contra
 * un string (`'externo'`/`'interno'`), pero un `Select` con `->options(TipoMantenimiento::class)`
 * mantiene el estado en vivo como instancia del enum, no como string — la comparación `===` nunca
 * era verdadera y ambos campos quedaban permanentemente ocultos sin importar la selección. Se
 * compara ahora contra el caso del enum directamente.
 */
class PlanMantenimientoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Equipo y período')
                    ->description('Qué equipo cubre el plan, para qué año y con qué frecuencia')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->columns(2)
                    ->schema([
                        Select::make('equipo_id')
                            ->relationship('equipo', 'nombre')
                            ->searchable()
                            ->preload()
                            ->label('Equipo')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('anio')
                            ->label('Año')
                            ->numeric()
                            ->required(),
                        Select::make('frecuencia_anual')
                            ->label('Frecuencia anual')
                            ->options(FrecuenciaAnual::class)
                            ->required(),
                    ]),

                Section::make('Ejecución y costo')
                    ->description('Quién ejecuta el mantenimiento y su costo de referencia')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->columns(2)
                    ->schema([
                        Select::make('tipo_mantenimiento')
                            ->label('Tipo de mantenimiento')
                            ->options(TipoMantenimiento::class)
                            ->live()
                            ->required()
                            ->columnSpanFull(),
                        Select::make('proveedor_id')
                            ->relationship('proveedor', 'nombre')
                            ->searchable()
                            ->preload()
                            ->label('Proveedor')
                            ->visible(fn ($get) => $get('tipo_mantenimiento') === TipoMantenimiento::Externo),
                        Select::make('responsable_interno_id')
                            ->relationship('responsableInterno', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Responsable interno')
                            ->visible(fn ($get) => $get('tipo_mantenimiento') === TipoMantenimiento::Interno),
                        Select::make('convenio_id')
                            ->relationship('convenio', 'nombre')
                            ->searchable()
                            ->preload()
                            ->label('Convenio asociado'),
                        TextInput::make('costo_anual_referencia')
                            ->label('Costo anual de referencia')
                            ->numeric()
                            ->prefix('$'),
                    ]),
            ]);
    }
}
