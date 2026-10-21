<?php

namespace App\Filament\Pages;

use App\Enums\Criticidad;
use App\Enums\EstadoEjecucion;
use App\Filament\Support\BitacoraMensual;
use App\Models\EjecucionMensual;
use App\Models\PlanMantenimiento;
use App\Models\Recinto;
use App\Models\ServicioClinico;
use App\Support\AlcanceTecnicoInterno;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * RF-79 (Módulo 19): vista anual consolidada de la bitácora, igual que la planilla: una fila por
 * plan del año y 12 columnas de meses con los símbolos X / √ / ꓣ.
 *
 * Los planes visibles los acotan los global scopes por recinto (RF-63). Cada celda abre el mismo
 * formulario de la grilla del plan (`BitacoraMensual`, con RN-02, RF-66 y RF-74). El técnico interno
 * ve todos los planes, pero solo registra en los que tiene asignados (RF-20), y un rol sin permiso
 * de edición de la bitácora (Jefatura) ve la grilla en solo lectura. El pie de cada mes muestra
 * realizadas / programadas del conjunto filtrado, y la columna del mes en curso va resaltada.
 */
class BitacoraAnual extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|\UnitEnum|null $navigationGroup = 'Mantenimiento';

    protected static ?string $navigationLabel = 'Bitácora anual';

    protected static ?string $title = 'Bitácora anual';

    protected static ?string $slug = 'bitacora-anual';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('view.bitacora_anual');
    }

    public function getSubheading(): ?string
    {
        return collect(EstadoEjecucion::cases())
            ->map(fn (EstadoEjecucion $estado): string => "{$estado->simbolo()} {$estado->getLabel()}")
            ->implode(' · ').' · Haz clic en un mes para registrar su ejecución.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PlanMantenimiento::query()->with(['equipo.servicioClinico', 'ejecucionesMensuales']))
            ->columns([
                TextColumn::make('equipo.n_inventario')
                    ->label('N° inventario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('equipo.nombre')
                    ->label('Equipo')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('equipo.servicioClinico.nombre')
                    ->label('Servicio clínico')
                    ->toggleable(),
                TextColumn::make('equipo.criticidad')
                    ->label('Criticidad')
                    ->badge(),
                TextColumn::make('frecuencia_anual')
                    ->label('Frec.')
                    ->formatStateUsing(fn ($state): string => ($state?->value ?? $state).'x'),
                ...array_map(fn (int $mes): Column => $this->columnaMes($mes), array_keys(BitacoraMensual::MESES)),
            ])
            ->filters([
                SelectFilter::make('anio')
                    ->label('Año')
                    ->options(fn (): array => PlanMantenimiento::query()
                        ->distinct()
                        ->pluck('anio')
                        ->push(now()->year)
                        ->unique()
                        ->sortDesc()
                        ->mapWithKeys(fn ($anio): array => [(string) $anio => (string) $anio])
                        ->all())
                    ->default((string) now()->year)
                    ->selectablePlaceholder(false),
                SelectFilter::make('recinto')
                    ->label('Recinto')
                    ->options(fn (): array => Recinto::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query) => $query->whereHas('equipo', fn (Builder $equipo) => $equipo->where('recinto_id', $data['value'])),
                    )),
                SelectFilter::make('servicio_clinico')
                    ->label('Servicio clínico')
                    ->options(fn (): array => ServicioClinico::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query) => $query->whereHas('equipo', fn (Builder $equipo) => $equipo->where('servicio_clinico_id', $data['value'])),
                    )),
                SelectFilter::make('criticidad')
                    ->label('Criticidad')
                    ->options(Criticidad::class)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query) => $query->whereHas('equipo', fn (Builder $equipo) => $equipo->where('criticidad', $data['value'])),
                    )),
            ])
            ->defaultSort('equipo.nombre')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading('Sin planes de mantenimiento para el año y los filtros elegidos');
    }

    private function columnaMes(int $mes): Column
    {
        $esMesActual = fn (): bool => $mes === now()->month
            && (int) ($this->getTableFilterState('anio')['value'] ?? now()->year) === now()->year;
        $resaltado = fn (): array => $esMesActual()
            ? ['style' => 'background-color: color-mix(in oklab, var(--primary-500) 12%, transparent);']
            : [];

        return TextColumn::make("mes_{$mes}")
            ->label(BitacoraMensual::MESES_ABREVIADOS[$mes])
            ->alignCenter()
            ->badge()
            ->state(fn (PlanMantenimiento $record): string => $this->estadoDelMes($record, $mes)->simbolo())
            ->color(fn (PlanMantenimiento $record): string => $this->estadoDelMes($record, $mes)->getColor())
            ->tooltip(function (PlanMantenimiento $record) use ($mes): string {
                $ejecucion = $record->ejecucionesMensuales->firstWhere('mes', $mes);
                $estado = $this->estadoDelMes($record, $mes);

                return BitacoraMensual::MESES[$mes].': '.$estado->getLabel()
                    .($ejecucion?->fecha_real ? ' · '.$ejecucion->fecha_real->format('d-m-Y') : '');
            })
            ->extraHeaderAttributes($resaltado)
            ->extraCellAttributes($resaltado)
            ->action($this->accionEditarMes($mes))
            ->summarize(Summarizer::make()
                ->label('Realizadas / programadas')
                ->using(fn (QueryBuilder $query): string => $this->resumenDelMes($query, $mes)));
    }

    private function estadoDelMes(PlanMantenimiento $plan, int $mes): EstadoEjecucion
    {
        return $plan->ejecucionesMensuales->firstWhere('mes', $mes)?->estado ?? EstadoEjecucion::SinProgramar;
    }

    private function accionEditarMes(int $mes): Action
    {
        $ejecucionDe = fn (PlanMantenimiento $plan): ?EjecucionMensual => $plan->ejecucionesMensuales()->where('mes', $mes)->first();

        return Action::make("editarMes{$mes}")
            ->modalHeading(fn (PlanMantenimiento $record): string => 'Bitácora — '.BitacoraMensual::MESES[$mes].' '.$record->anio.' · '.$record->equipo?->nombre)
            ->modalSubmitActionLabel('Guardar')
            ->schema(fn (PlanMantenimiento $record): array => BitacoraMensual::campos(fn (Get $get): ?EjecucionMensual => $ejecucionDe($record)))
            ->fillForm(fn (PlanMantenimiento $record): array => BitacoraMensual::datos($ejecucionDe($record)))
            ->action(fn (PlanMantenimiento $record, array $data) => BitacoraMensual::guardar($record, $mes, $data))
            ->successNotificationTitle('Bitácora actualizada')
            ->visible(fn (PlanMantenimiento $record): bool => (bool) auth()->user()?->can('update.ejecucion_mensual')
                && AlcanceTecnicoInterno::puedeGestionarPlan($record));
    }

    /**
     * Realizadas / programadas del mes en el conjunto filtrado. "Programadas" cuenta todo mes con
     * marca (programado, realizado o reprogramado), igual que el indicador de cumplimiento (RN-03).
     */
    private function resumenDelMes(QueryBuilder $planes, int $mes): string
    {
        $ejecuciones = EjecucionMensual::query()
            ->whereIn('plan_mantenimiento_id', (clone $planes)->select('id'))
            ->where('mes', $mes)
            ->where('estado', '!=', EstadoEjecucion::SinProgramar);

        $programadas = (clone $ejecuciones)->count();
        $realizadas = (clone $ejecuciones)->where('estado', EstadoEjecucion::Realizado)->count();

        return "{$realizadas} / {$programadas}";
    }
}
