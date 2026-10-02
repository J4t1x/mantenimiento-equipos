<?php

namespace App\Filament\Resources\EjecucionesMensuales\Tables;

use App\Enums\EstadoEjecucion;
use App\Models\EjecucionMensual;
use App\Models\PlanMantenimiento;
use App\Support\AlcanceTecnicoInterno;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * RF-66 (Módulo 15): columnas de causa y plazo de la reprogramación, y filtro de reprogramaciones
 * vencidas de equipos críticos (enlazado desde la alerta del Escritorio).
 */
class EjecucionesMensualesTable
{
    public const FILTRO_REPROGRAMACIONES_VENCIDAS = 'reprogramacion_vencida';

    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('planMantenimiento.equipo.nombre')
                    ->label('Equipo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('planMantenimiento.anio')
                    ->label('Año')
                    ->sortable(),
                TextColumn::make('mes')
                    ->label('Mes')
                    ->formatStateUsing(fn (int $state) => self::MESES[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('fecha_real')
                    ->label('Fecha real')
                    ->date('d-m-Y')
                    ->placeholder('—'),
                TextColumn::make('plazo_reprogramacion')
                    ->label('Plazo reprogramación')
                    ->state(fn (EjecucionMensual $record) => $record->plazoReprogramacion())
                    ->date('d-m-Y')
                    ->color(fn (EjecucionMensual $record): ?string => $record->plazoReprogramacion()?->isPast() ? 'danger' : null)
                    ->placeholder('—'),
                TextColumn::make('observaciones')
                    ->label('Observaciones / causa')
                    ->limit(60)
                    ->tooltip(fn (EjecucionMensual $record): ?string => $record->observaciones)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('documento_justificacion')
                    ->label('Documento de justificación')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(EstadoEjecucion::class),
                // RF-48 (Módulo 12): filtrar por año y por equipo, igual que Planificación MP.
                SelectFilter::make('anio')
                    ->label('Año')
                    ->options(fn () => PlanMantenimiento::query()->distinct()->orderByDesc('anio')->pluck('anio', 'anio'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $value) => $q->whereHas('planMantenimiento', fn (Builder $q2) => $q2->where('anio', $value)),
                    )),
                SelectFilter::make('equipo')
                    ->label('Equipo')
                    ->relationship('planMantenimiento.equipo', 'nombre')
                    ->searchable()
                    ->preload(),
                Filter::make(self::FILTRO_REPROGRAMACIONES_VENCIDAS)
                    ->label('Críticos con reprogramación vencida (más de 30 días)')
                    ->query(fn (Builder $query): Builder => $query->reprogramacionesVencidas()),
            ])
            ->recordActions([
                // RF-20: un técnico interno solo edita la ejecución de los equipos que tiene
                // asignados como responsable — el resto de los roles no tiene esta restricción.
                EditAction::make()
                    ->visible(fn (EjecucionMensual $record): bool => AlcanceTecnicoInterno::puedeGestionarPlan($record->planMantenimiento)),
            ]);
    }
}
