<?php

namespace App\Filament\Resources\Equipos\Schemas;

use App\Actions\CalcularCumplimientoMpAction;
use App\Actions\CalcularGastoCorrectivoAction;
use App\Models\Equipo;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * RF-37 del SRS: los ~20 campos de la ficha de un equipo se agrupan en secciones temáticas en vez
 * de una lista plana sin jerarquía visual — mismo criterio de agrupación que `EquipoForm`, para
 * que formulario y ficha se lean igual. Los datos de auditoría (creado/actualizado/eliminado)
 * pasan a una sección aparte y colapsada por defecto: son de consulta ocasional, no la información
 * principal de la ficha.
 */
class EquipoInfolist
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
                        TextEntry::make('nombre')
                            ->label('Nombre')
                            ->columnSpanFull(),
                        TextEntry::make('marca')
                            ->label('Marca')
                            ->placeholder('—'),
                        TextEntry::make('modelo')
                            ->label('Modelo')
                            ->placeholder('—'),
                        TextEntry::make('serie')
                            ->label('N° de serie')
                            ->placeholder('—'),
                        TextEntry::make('n_inventario')
                            ->label('N° de inventario'),
                    ]),

                Section::make('Ubicación y clasificación')
                    ->description('Dónde está el equipo y a qué catálogo pertenece')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('recinto.nombre')
                            ->label('Recinto'),
                        TextEntry::make('servicioClinico.nombre')
                            ->label('Servicio clínico'),
                        TextEntry::make('clase.nombre')
                            ->label('Clase'),
                        TextEntry::make('subclase.nombre')
                            ->label('Subclase')
                            ->placeholder('—'),
                    ]),

                Section::make('Ficha técnica')
                    ->description('Antigüedad, vida útil y régimen de propiedad')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('anio_adquisicion')
                            ->label('Año de adquisición')
                            ->numeric()
                            ->placeholder('—'),
                        TextEntry::make('vida_util_anios')
                            ->label('Vida útil (años)')
                            ->numeric()
                            ->placeholder('—'),
                        TextEntry::make('vida_util_residual')
                            ->label('Vida útil residual')
                            ->placeholder('—'),
                        TextEntry::make('propiedad')
                            ->label('Propiedad')
                            ->badge(),
                    ]),

                Section::make('Estado y garantía')
                    ->description('Condición actual, criticidad clínica y cobertura de garantía')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge(),
                        TextEntry::make('criticidad')
                            ->label('Criticidad')
                            ->badge(),
                        IconEntry::make('en_garantia')
                            ->label('En garantía')
                            ->boolean(),
                        TextEntry::make('garantia_anio_vencimiento')
                            ->label('Vence garantía')
                            ->numeric()
                            ->placeholder('—'),
                        IconEntry::make('activo')
                            ->label('Activo')
                            ->boolean(),
                    ]),

                Section::make('Plan de mantenimiento')
                    ->description('Si el equipo está bajo un plan de mantenimiento preventivo')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->columns(2)
                    ->schema([
                        IconEntry::make('bajo_plan_mp')
                            ->label('Bajo plan MP')
                            ->boolean(),
                        TextEntry::make('anio_ingreso_plan')
                            ->label('Año ingreso al plan')
                            ->numeric()
                            ->placeholder('—'),
                    ]),

                Section::make('Cumplimiento de mantenimiento preventivo')
                    ->description('RN-03 — ejecutados / programados del año en curso')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->schema([
                        TextEntry::make('cumplimiento_mp')
                            ->label('Cumplimiento '.now()->year)
                            ->state(function (Equipo $record): string {
                                $cumplimiento = app(CalcularCumplimientoMpAction::class)
                                    ->paraEquipo($record->id, now()->year);

                                if ($cumplimiento['porcentaje'] === null) {
                                    return 'Sin plan de mantenimiento programado este año';
                                }

                                return "{$cumplimiento['porcentaje']}% ({$cumplimiento['ejecutados']} de {$cumplimiento['programados']} programados)";
                            }),
                    ]),

                Section::make('Gasto de mantenimiento correctivo')
                    ->description('RF-25 — suma de eventos correctivos del año en curso')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->schema([
                        TextEntry::make('gasto_correctivo')
                            ->label('Gasto MC '.now()->year)
                            ->money('CLP')
                            ->state(fn (Equipo $record): float => app(CalcularGastoCorrectivoAction::class)
                                ->paraEquipo($record->id, now()->year)),
                    ]),

                Section::make('Registro')
                    ->description('Trazabilidad de creación y modificación (RF-36)')
                    ->icon(Heroicon::OutlinedClock)
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
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
                            ->visible(fn (Equipo $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
