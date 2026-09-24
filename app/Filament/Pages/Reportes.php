<?php

namespace App\Filament\Pages;

use App\Actions\CalcularCumplimientoMpAction;
use App\Actions\ObtenerDetalleGastoAction;
use App\Actions\ObtenerFilasCatastroPlanAction;
use App\Exports\CatastroPlanExport;
use App\Exports\CumplimientoExport;
use App\Exports\DetalleGastoExport;
use App\Filament\Widgets\CumplimientoMpWidget;
use App\Models\Recinto;
use App\Models\ServicioClinico;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Vista del módulo de Reportes (BRIEF §4/§5). RF-31, RF-32 y RF-33 exportan a Excel.
 *
 * RF-62 (Módulo 14, reemplaza la distribución de RF-40): la página se arma solo con componentes
 * de Schema de Filament (sin vista Blade propia: el panel usa el CSS ya compilado de Filament, y
 * las clases Tailwind sueltas de una vista propia no se compilan):
 *
 * 1. Un único formulario de alcance (año, recinto, servicio clínico — los campos de RF-54) en la
 *    parte superior, que aplica a los tres reportes. Reemplaza el modal de filtros que tenía cada
 *    botón, que obligaba a completar el mismo formulario tres veces. El formulario es propio de la
 *    página (`form()` + `EmbeddedSchema`), no `HasFiltersForm` del Escritorio, así que no pasa por
 *    el error 500 de Filament 5.7.8 + Livewire 4.4.3 descrito en `CumplimientoMpWidget`.
 * 2. Una grilla de tres secciones, una por reporte, con un resumen del alcance elegido que se
 *    recalcula al cambiar los filtros — reutiliza las mismas Actions que alimentan cada export,
 *    para que el resumen y el archivo no puedan discrepar.
 * 3. Un botón "Descargar Excel" por reporte, sin modal, que usa el alcance del formulario.
 *
 * El contenido y formato de los tres exports no cambia (RNF-09).
 */
class Reportes extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?string $title = 'Reportes';

    protected static ?int $navigationSort = 90;

    /**
     * @var array{anio?: int|string|null, recinto_id?: int|string|null, servicio_clinico_id?: int|string|null}|null
     */
    public ?array $filtros = [];

    /**
     * RF-35 (BRIEF §5): acceso a la página restringido por permiso de rol.
     */
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('view.reportes');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getSubheading(): ?string
    {
        return 'Exportación a Excel con el mismo formato de la planilla vigente. El alcance elegido se aplica a los tres reportes.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'md' => 3])
                    ->schema([
                        TextInput::make('anio')
                            ->label('Año')
                            ->numeric()
                            ->required()
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->default(now()->year)
                            ->live(debounce: 500),
                        Select::make('recinto_id')
                            ->label('Recinto')
                            ->options(fn () => Recinto::query()->orderBy('nombre')->pluck('nombre', 'id'))
                            ->searchable()
                            ->placeholder('Todos')
                            ->live(),
                        Select::make('servicio_clinico_id')
                            ->label('Servicio clínico')
                            ->options(fn () => ServicioClinico::query()->orderBy('nombre')->pluck('nombre', 'id'))
                            ->searchable()
                            ->placeholder('Todos')
                            ->live(),
                    ]),
            ])
            ->statePath('filtros');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Alcance')
                    ->icon(Heroicon::OutlinedFunnel)
                    ->compact()
                    ->schema([EmbeddedSchema::make('form')]),

                Grid::make(['default' => 1, 'lg' => 3])
                    ->schema([
                        $this->seccionCatastroPlan(),
                        $this->seccionCumplimiento(),
                        $this->seccionDetalleGasto(),
                    ]),
            ]);
    }

    private function seccionCatastroPlan(): Section
    {
        return Section::make('Catastro + plan anual')
            ->icon(Heroicon::OutlinedClipboardDocumentList)
            ->description('Cada equipo con plan de MP y sus 12 meses marcados (X, √, ꓣ).')
            ->schema(fn (): array => $this->resumen(function (int $anio, ?int $recintoId, ?int $servicioClinicoId): array {
                $planes = app(ObtenerFilasCatastroPlanAction::class)->contar($anio, $recintoId, $servicioClinicoId);

                return [
                    $this->cifra(number_format($planes, 0, ',', '.'), $planes > 0 ? 'primary' : 'gray'),
                    Text::make($planes === 1 ? "equipo con plan de MP en {$anio}" : "equipos con plan de MP en {$anio}"),
                ];
            }))
            ->footer([Actions::make([$this->descargarCatastroPlanAction()])]);
    }

    private function seccionCumplimiento(): Section
    {
        return Section::make('Indicador de cumplimiento')
            ->icon(Heroicon::OutlinedChartBar)
            ->description('Ejecutadas sobre programadas: total, EQC, EQR e IM ≥ 12.')
            ->schema(fn (): array => $this->resumen(function (int $anio, ?int $recintoId, ?int $servicioClinicoId): array {
                $total = app(CalcularCumplimientoMpAction::class)
                    ->execute($anio, recintoId: $recintoId, servicioClinicoId: $servicioClinicoId)['total'];

                return [
                    $this->cifra(
                        $total['porcentaje'] === null ? 'Sin datos' : "{$total['porcentaje']}%",
                        CumplimientoMpWidget::colorParaPorcentaje($total['porcentaje']),
                    ),
                    Text::make("{$total['ejecutados']} de {$total['programados']} programadas en {$anio}"),
                ];
            }))
            ->footer([Actions::make([$this->descargarCumplimientoAction()])]);
    }

    private function seccionDetalleGasto(): Section
    {
        return Section::make('Detalle de gasto')
            ->icon(Heroicon::OutlinedBanknotes)
            ->description('Gasto ejecutado en mantenimiento preventivo y correctivo.')
            ->schema(fn (): array => $this->resumen(function (int $anio, ?int $recintoId, ?int $servicioClinicoId): array {
                $gasto = app(ObtenerDetalleGastoAction::class)->execute($anio, $recintoId, $servicioClinicoId);
                $total = $gasto['mp']['ejecutado'] + $gasto['mc']['ejecutado'];

                return [
                    $this->cifra(self::pesos($total), $total > 0 ? 'primary' : 'gray'),
                    Text::make('Preventivo '.self::pesos($gasto['mp']['ejecutado']).' · Correctivo '.self::pesos($gasto['mc']['ejecutado'])),
                ];
            }))
            ->footer([Actions::make([$this->descargarDetalleGastoAction()])]);
    }

    public function descargarCatastroPlanAction(): Action
    {
        return $this->accionDescarga('descargarCatastroPlan', 'catastro-plan', CatastroPlanExport::class);
    }

    public function descargarCumplimientoAction(): Action
    {
        return $this->accionDescarga('descargarCumplimiento', 'cumplimiento-mp', CumplimientoExport::class);
    }

    public function descargarDetalleGastoAction(): Action
    {
        return $this->accionDescarga('descargarDetalleGasto', 'detalle-gasto', DetalleGastoExport::class);
    }

    /**
     * @param  class-string<CatastroPlanExport|CumplimientoExport|DetalleGastoExport>  $export
     */
    private function accionDescarga(string $nombre, string $prefijoArchivo, string $export): Action
    {
        return Action::make($nombre)
            ->label('Descargar Excel')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->action(function () use ($prefijoArchivo, $export): BinaryFileResponse {
                $filtros = $this->form->getState();
                ['anio' => $anio, 'recintoId' => $recintoId, 'servicioClinicoId' => $servicioClinicoId] = $this->alcance($filtros);

                return Excel::download(
                    new $export($anio, $recintoId, $servicioClinicoId),
                    $this->nombreArchivo($prefijoArchivo, $anio, $recintoId, $servicioClinicoId),
                );
            });
    }

    /**
     * Resumen de una sección para el alcance actual del formulario; si el año todavía no es válido
     * (p. ej. mientras se escribe), muestra un aviso en vez de calcular sobre un año incompleto.
     *
     * @param  callable(int, int|null, int|null): array<int, Text>  $calcular
     * @return array<int, Text>
     */
    private function resumen(callable $calcular): array
    {
        $alcance = $this->alcance($this->filtros ?? []);

        if ($alcance['anio'] === null) {
            return [Text::make('Ingrese un año válido para ver el resumen.')->color('gray')];
        }

        return $calcular($alcance['anio'], $alcance['recintoId'], $alcance['servicioClinicoId']);
    }

    private function cifra(string $valor, string $color): Text
    {
        return Text::make($valor)
            ->size(TextSize::Large)
            ->weight(FontWeight::Bold)
            ->color($color);
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array{anio: int|null, recintoId: int|null, servicioClinicoId: int|null}
     */
    private function alcance(array $filtros): array
    {
        $anio = filter_var($filtros['anio'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]]);

        return [
            'anio' => $anio === false ? null : $anio,
            'recintoId' => filled($filtros['recinto_id'] ?? null) ? (int) $filtros['recinto_id'] : null,
            'servicioClinicoId' => filled($filtros['servicio_clinico_id'] ?? null) ? (int) $filtros['servicio_clinico_id'] : null,
        ];
    }

    /**
     * Nombre de archivo de antes (`{prefijo}-{año}.xlsx`) más el recinto y/o servicio clínico cuando
     * hay filtro, para distinguir varias descargas del mismo año.
     */
    private function nombreArchivo(string $prefijo, int $anio, ?int $recintoId, ?int $servicioClinicoId): string
    {
        $partes = [
            $prefijo,
            $anio,
            $recintoId !== null ? Str::slug((string) Recinto::find($recintoId)?->nombre) : null,
            $servicioClinicoId !== null ? Str::slug((string) ServicioClinico::find($servicioClinicoId)?->nombre) : null,
        ];

        return implode('-', array_filter($partes, 'filled')).'.xlsx';
    }

    private static function pesos(float $monto): string
    {
        return '$'.number_format($monto, 0, ',', '.');
    }
}
