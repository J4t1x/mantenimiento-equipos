<?php

namespace App\Filament\Resources\EjecucionesMensuales\Schemas;

use App\Enums\EstadoEjecucion;
use App\Models\EjecucionMensual;
use App\Rules\TransicionBitacoraValida;
use App\Support\AlcanceTecnicoInterno;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class EjecucionMensualForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('plan_mantenimiento_id')
                    ->relationship(
                        // RF-20: un técnico interno solo puede elegir planes que tiene asignados
                        // como responsable — sin efecto para el resto de los roles.
                        'planMantenimiento',
                        'anio',
                        modifyQueryUsing: fn ($query) => AlcanceTecnicoInterno::limitarPlanes($query)->with('equipo'),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->equipo?->nombre} — {$record->anio}")
                    ->searchable()
                    ->preload()
                    ->label('Plan de mantenimiento')
                    ->required(),
                Select::make('mes')
                    ->label('Mes')
                    ->options([
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                    ])
                    ->required(),
                Select::make('estado')
                    ->label('Estado')
                    ->options(EstadoEjecucion::class)
                    ->default('sin_programar')
                    ->required()
                    ->rule(fn (?EjecucionMensual $record) => new TransicionBitacoraValida($record)),
                DatePicker::make('fecha_real')
                    ->label('Fecha real de ejecución'),
                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->columnSpanFull(),
            ]);
    }
}
