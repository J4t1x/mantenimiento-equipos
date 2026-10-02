<?php

namespace App\Filament\Resources\EjecucionesMensuales\Schemas;

use App\Enums\EstadoEjecucion;
use App\Exceptions\ReprogramacionSinCausaException;
use App\Models\EjecucionMensual;
use App\Rules\TransicionBitacoraValida;
use App\Support\AlcanceTecnicoInterno;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->live()
                    ->rule(fn (?EjecucionMensual $record) => new TransicionBitacoraValida($record)),
                DatePicker::make('fecha_real')
                    ->label('Fecha real de ejecución'),
                // RF-66 (RN-09): la reprogramación exige su causa; mismo criterio que la guarda de
                // `EjecucionMensual`.
                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->required(fn (Get $get): bool => EstadoEjecucion::desde($get('estado')) === EstadoEjecucion::Reprogramado)
                    ->validationMessages(['required' => ReprogramacionSinCausaException::MENSAJE])
                    ->helperText('Obligatorio al reprogramar: indica la causa (Res. Ex. 1341/2017).')
                    ->columnSpanFull(),
                // RF-74 (Res. Ex. 1341/2017 §7.4.ii): referencia al documento formal de la justificación.
                TextInput::make('documento_justificacion')
                    ->label('N° de documento de justificación')
                    ->placeholder('Ej.: Memo N° 45')
                    ->maxLength(100)
                    ->visible(fn (Get $get): bool => EstadoEjecucion::desde($get('estado')) === EstadoEjecucion::Reprogramado),
            ]);
    }
}
