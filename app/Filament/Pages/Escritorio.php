<?php

namespace App\Filament\Pages;

use App\Enums\Periodo;
use App\Filament\Widgets\CumplimientoMpWidget;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Dashboard;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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
 * `#[Reactive]`; el resto los ignora.
 */
class Escritorio extends Dashboard
{
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
                Grid::make(['default' => 1, 'md' => 2])
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
                Section::make('Período del indicador de cumplimiento')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->compact()
                    ->collapsible()
                    ->schema([EmbeddedSchema::make('form')])
                    ->visible(fn (): bool => CumplimientoMpWidget::canView()),
                $this->getWidgetsContentComponent(),
            ]);
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
