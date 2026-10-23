<?php

namespace App\Filament\Pages;

use App\Enums\Periodo;
use App\Filament\Widgets\AlertasConveniosWidget;
use App\Filament\Widgets\CatastroAlertasWidget;
use App\Filament\Widgets\ConveniosEncargadoWidget;
use App\Filament\Widgets\CumplimientoMpMensualWidget;
use App\Filament\Widgets\CumplimientoMpWidget;
use App\Filament\Widgets\DistribucionCatastroCriticidadWidget;
use App\Filament\Widgets\DistribucionCatastroWidget;
use App\Filament\Widgets\GastoMensualWidget;
use App\Filament\Widgets\GastoMpWidget;
use App\Filament\Widgets\IndicadoresGeneralesWidget;
use App\Filament\Widgets\MisPlanesDelMesWidget;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Dashboard;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

/**
 * RF-21 (cierre, Módulo 17): Escritorio con selector de período para el indicador de cumplimiento
 * de MP (RF-21/RF-22).
 *
 * El intento original con `HasFiltersForm` producía un error 500 en Filament 5.7.8 + Livewire 4.4.3
 * (`trim(): Argument #1 must be of type string, array given` al renderizar el placeholder del
 * widget). Filament pasa los filtros a cada widget como el array `pageFilters`, y ese array termina
 * como atributo del placeholder. Aquí el formulario es propio de la página (mismo rodeo que
 * `Reportes`, RF-62) y el período llega a los widgets como dos valores escalares, `anio` y
 * `periodo`, vía `getWidgetData()`. Los widgets que los usan los declaran como propiedades
 * `#[Reactive]` con el trait `RecibePeriodoDelEscritorio`; el resto los ignora.
 *
 * Corrección de RF-21 (SPEC-ME-002): antes solo `CumplimientoMpWidget` recibía el período. Hoy lo
 * reciben todos los widgets de {@see self::WIDGETS_CON_PERIODO}. Los gráficos lo usan como valor de
 * su filtro de año (`FiltroDeAnioSincronizadoConEscritorio`).
 *
 * SPEC-ME-007: el Escritorio se arma como un dashboard por grupos ({@see self::GRUPOS}), en vez de
 * una sola grilla de widgets ordenados por `$sort`. Cada grupo es una sección sin tarjeta, con
 * título, que solo aparece si el usuario ve alguno de sus widgets. Cada fila del grupo es una grilla
 * propia. El período queda en una barra compacta, y el subtítulo indica qué período se muestra.
 */
class Escritorio extends Dashboard
{
    /**
     * Widgets que dependen del año o del período elegidos en el Escritorio.
     *
     * @var array<int, class-string<Widget>>
     */
    public const WIDGETS_CON_PERIODO = [
        CumplimientoMpWidget::class,
        CumplimientoMpMensualWidget::class,
        GastoMensualWidget::class,
        GastoMpWidget::class,
        CatastroAlertasWidget::class,
        AlertasConveniosWidget::class,
        ConveniosEncargadoWidget::class,
        IndicadoresGeneralesWidget::class,
    ];

    /**
     * Grupos del Escritorio, en orden. Cada fila es una grilla con sus columnas en pantallas grandes
     * (`lg`) y sus widgets; el ancho de cada widget dentro de la fila lo define su `$columnSpan`.
     *
     * @var array<string, array{titulo: string, descripcion: string, icono: Heroicon, filas: array<int, array{columnas: int, widgets: array<int, class-string<Widget>>}>}>
     */
    public const GRUPOS = [
        'mi_trabajo' => [
            'titulo' => 'Mi trabajo del mes',
            'descripcion' => 'Mantenciones de los equipos que tienes asignados.',
            'icono' => Heroicon::OutlinedClipboardDocumentCheck,
            'filas' => [['columnas' => 1, 'widgets' => [MisPlanesDelMesWidget::class]]],
        ],
        'atencion' => [
            'titulo' => 'Requiere atención',
            'descripcion' => 'Solo se muestran las alertas con casos pendientes.',
            'icono' => Heroicon::OutlinedBellAlert,
            'filas' => [['columnas' => 1, 'widgets' => [CatastroAlertasWidget::class, AlertasConveniosWidget::class]]],
        ],
        'preventivo' => [
            'titulo' => 'Mantenimiento preventivo',
            'descripcion' => 'Cumplimiento del plan y gasto del período, con su evolución mes a mes.',
            'icono' => Heroicon::OutlinedWrenchScrewdriver,
            'filas' => [
                ['columnas' => 3, 'widgets' => [CumplimientoMpWidget::class, GastoMpWidget::class]],
                ['columnas' => 2, 'widgets' => [CumplimientoMpMensualWidget::class, GastoMensualWidget::class]],
            ],
        ],
        'catastro' => [
            'titulo' => 'Catastro de equipos',
            'descripcion' => 'Composición del catastro activo y mantenimiento correctivo del período.',
            'icono' => Heroicon::OutlinedCube,
            'filas' => [
                ['columnas' => 1, 'widgets' => [IndicadoresGeneralesWidget::class]],
                ['columnas' => 2, 'widgets' => [DistribucionCatastroWidget::class, DistribucionCatastroCriticidadWidget::class]],
            ],
        ],
        'convenios' => [
            'titulo' => 'Convenios',
            'descripcion' => 'Convenios activos y su ejecución del año.',
            'icono' => Heroicon::OutlinedDocumentCheck,
            'filas' => [['columnas' => 1, 'widgets' => [ConveniosEncargadoWidget::class]]],
        ],
    ];

    /**
     * @var array{anio?: int|string|null, periodo?: string|null}|null
     */
    public ?array $periodoEscritorio = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 2, 'md' => 4, 'xl' => 6])
                    ->schema([
                        TextInput::make('anio')
                            ->label('Año')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->default(now()->year)
                            ->live(debounce: 500),
                        Select::make('periodo')
                            ->label('Período')
                            ->options(Periodo::opcionesAgrupadas())
                            ->default(Periodo::Anual->value)
                            ->selectablePlaceholder(false)
                            ->live(),
                    ]),
            ])
            ->statePath('periodoEscritorio');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedSchema::make('form')
                    ->visible(fn (): bool => collect(self::WIDGETS_CON_PERIODO)->contains(fn (string $widget): bool => $widget::canView())),
                ...collect(self::GRUPOS)
                    ->map(fn (array $grupo, string $clave): Section => $this->seccionDeGrupo($clave, $grupo))
                    ->values()
                    ->all(),
            ]);
    }

    public function getSubheading(): ?string
    {
        ['anio' => $anio, 'periodo' => $periodo] = $this->getWidgetData();
        $periodo = Periodo::from($periodo);

        return 'Indicadores de '.($periodo->esAnual() ? "todo {$anio}" : $periodo->descripcion($anio)).'. Las alertas operativas se calculan siempre a la fecha de hoy.';
    }

    /**
     * Widgets visibles para el usuario actual, en el orden en que aparecen en el Escritorio.
     *
     * @return array<int, class-string<Widget>>
     */
    public static function widgetsVisibles(): array
    {
        return collect(self::GRUPOS)
            ->flatMap(fn (array $grupo): array => collect($grupo['filas'])->flatMap(fn (array $fila): array => $fila['widgets'])->all())
            ->filter(fn (string $widget): bool => $widget::canView())
            ->values()
            ->all();
    }

    /**
     * @param  array{titulo: string, descripcion: string, icono: Heroicon, filas: array<int, array{columnas: int, widgets: array<int, class-string<Widget>>}>}  $grupo
     */
    private function seccionDeGrupo(string $clave, array $grupo): Section
    {
        $widgets = collect($grupo['filas'])->flatMap(fn (array $fila): array => $fila['widgets']);

        return Section::make($grupo['titulo'])
            ->key("grupo-{$clave}")
            ->description($grupo['descripcion'])
            ->icon($grupo['icono'])
            ->contained(false)
            ->schema(collect($grupo['filas'])
                ->map(fn (array $fila): Grid => Grid::make(['default' => 1, 'lg' => $fila['columnas']])
                    ->schema(fn (): array => $this->getWidgetsSchemaComponents($fila['widgets'])))
                ->all())
            ->visible(fn (): bool => $widgets->contains(fn (string $widget): bool => $widget::canView()));
    }

    /**
     * @return array{anio: int, periodo: string}
     */
    public function getWidgetData(): array
    {
        $anio = filter_var($this->periodoEscritorio['anio'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]]);
        $periodo = Periodo::tryFrom((string) ($this->periodoEscritorio['periodo'] ?? '')) ?? Periodo::Anual;

        return [
            'anio' => $anio === false ? now()->year : $anio,
            'periodo' => $periodo->value,
        ];
    }
}
