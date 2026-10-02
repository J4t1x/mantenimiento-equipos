<?php

namespace App\Filament\Resources\PlanMantenimientos\RelationManagers;

use App\Enums\EstadoEjecucion;
use App\Exceptions\ReprogramacionSinCausaException;
use App\Models\EjecucionMensual;
use App\Rules\TransicionBitacoraValida;
use App\Support\AlcanceTecnicoInterno;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * RF-38 del SRS: la bitácora de un equipo se muestra como una cuadrícula compacta de 12 meses con
 * celdas coloreadas por estado y una leyenda visible, en vez de la tabla vertical de 12 filas que
 * tenía antes este relation manager.
 *
 * RN-01 garantiza que un plan siempre tiene exactamente 12 `EjecucionMensual` (una por mes), así
 * que la grilla edita meses existentes — no crea ni elimina filas, a diferencia del CRUD genérico
 * que tenía antes este relation manager.
 */
class EjecucionesMensualesRelationManager extends RelationManager
{
    protected static string $relationship = 'ejecucionesMensuales';

    protected static ?string $title = 'Bitácora mensual';

    protected string $view = 'filament.resources.plan-mantenimientos.relation-managers.ejecuciones-mensuales-grid';

    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    private const MESES_ABREVIADOS = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    ];

    private const SIMBOLOS = [
        'sin_programar' => '–',
        'programado' => 'X',
        'realizado' => '√',
        'reprogramado' => 'ꓣ',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $plan = $this->getOwnerRecord();

        /** @var Collection<int, EjecucionMensual> $ejecucionesPorMes */
        $ejecucionesPorMes = $plan->ejecucionesMensuales()->orderBy('mes')->get()->keyBy('mes');

        return [
            'anio' => $plan->anio,
            'ejecucionesPorMes' => $ejecucionesPorMes,
            'mesesAbreviados' => self::MESES_ABREVIADOS,
            'simbolos' => self::SIMBOLOS,
            'puedeGestionar' => AlcanceTecnicoInterno::puedeGestionarPlan($plan),
        ];
    }

    public function editarMesAction(): Action
    {
        return Action::make('editarMes')
            ->label(fn (array $arguments): string => self::MESES[$arguments['mes']] ?? 'Mes')
            ->modalHeading(fn (array $arguments): string => 'Bitácora — '.(self::MESES[$arguments['mes']] ?? ''))
            ->modalSubmitActionLabel('Guardar')
            ->schema([
                // Portador del mes en el propio estado del formulario: los closures de `rule()` a
                // nivel de campo no reciben los `$arguments` de la acción montada, a diferencia de
                // `fillForm()`/`action()`.
                Hidden::make('mes'),
                Select::make('estado')
                    ->label('Estado')
                    ->options(EstadoEjecucion::class)
                    ->required()
                    ->live()
                    ->rule(fn (Get $get) => new TransicionBitacoraValida($this->buscarEjecucion((int) $get('mes')))),
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
            ])
            ->fillForm(function (array $arguments): array {
                $ejecucion = $this->buscarEjecucion($arguments['mes']);

                return [
                    'mes' => $arguments['mes'],
                    'estado' => $ejecucion?->estado?->value ?? EstadoEjecucion::SinProgramar->value,
                    'fecha_real' => $ejecucion?->fecha_real,
                    'observaciones' => $ejecucion?->observaciones,
                    'documento_justificacion' => $ejecucion?->documento_justificacion,
                ];
            })
            ->action(function (array $arguments, array $data): void {
                unset($data['mes']);

                EjecucionMensual::query()->updateOrCreate(
                    ['plan_mantenimiento_id' => $this->getOwnerRecord()->id, 'mes' => $arguments['mes']],
                    $data,
                );
            })
            ->visible(fn (): bool => AlcanceTecnicoInterno::puedeGestionarPlan($this->getOwnerRecord()));
    }

    private function buscarEjecucion(int $mes): ?EjecucionMensual
    {
        return $this->getOwnerRecord()->ejecucionesMensuales()->where('mes', $mes)->first();
    }

    public function render(): View
    {
        return view($this->view, $this->getViewData());
    }
}
