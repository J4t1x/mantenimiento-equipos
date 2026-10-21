<?php

namespace App\Filament\Support;

use App\Enums\EstadoEjecucion;
use App\Exceptions\ReprogramacionSinCausaException;
use App\Models\EjecucionMensual;
use App\Models\PlanMantenimiento;
use App\Rules\TransicionBitacoraValida;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Registro de un mes de la bitácora, compartido por la grilla de 12 meses de un plan (RF-38,
 * `EjecucionesMensualesRelationManager`) y la vista anual consolidada (RF-79, `BitacoraAnual`):
 * nombres de los meses, campos del formulario con sus validaciones (RN-02, RF-66, RF-74) y guardado.
 *
 * RN-01 garantiza que un plan tiene una `EjecucionMensual` por mes, así que el formulario edita
 * meses existentes. El guardado usa `updateOrCreate` solo como resguardo.
 */
class BitacoraMensual
{
    public const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public const MESES_ABREVIADOS = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    ];

    /**
     * Campos del formulario del mes. `$ejecucionActual` recibe el estado del formulario (`Get`) y
     * devuelve el registro que se edita, para validar la transición de estado contra su valor
     * anterior (RN-02).
     *
     * @param  Closure(Get): ?EjecucionMensual  $ejecucionActual
     * @return array<int, Component|Field>
     */
    public static function campos(Closure $ejecucionActual): array
    {
        return [
            Select::make('estado')
                ->label('Estado')
                ->options(EstadoEjecucion::class)
                ->required()
                ->live()
                ->rule(fn (Get $get) => new TransicionBitacoraValida($ejecucionActual($get))),
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
        ];
    }

    /**
     * @return array{estado: string, fecha_real: mixed, observaciones: ?string, documento_justificacion: ?string}
     */
    public static function datos(?EjecucionMensual $ejecucion): array
    {
        return [
            'estado' => $ejecucion?->estado?->value ?? EstadoEjecucion::SinProgramar->value,
            'fecha_real' => $ejecucion?->fecha_real,
            'observaciones' => $ejecucion?->observaciones,
            'documento_justificacion' => $ejecucion?->documento_justificacion,
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function guardar(PlanMantenimiento $plan, int $mes, array $datos): void
    {
        EjecucionMensual::query()->updateOrCreate(
            ['plan_mantenimiento_id' => $plan->id, 'mes' => $mes],
            collect($datos)->only(['estado', 'fecha_real', 'observaciones', 'documento_justificacion'])->all(),
        );
    }
}
