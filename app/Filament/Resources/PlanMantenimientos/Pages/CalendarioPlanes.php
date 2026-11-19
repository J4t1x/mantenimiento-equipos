<?php

namespace App\Filament\Resources\PlanMantenimientos\Pages;

use App\Filament\Resources\PlanMantenimientos\PlanMantenimientoResource;
use App\Filament\Support\CalendarioDeMantenciones;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

/**
 * SPEC-ME-023: calendario de las mantenciones preventivas, como alternativa al listado de planes.
 *
 * La lógica está en {@see CalendarioDeMantenciones}, compartida con el widget del Escritorio. Aquí
 * el año se navega libremente y el estado (año, mes y filtros) queda en la URL.
 */
class CalendarioPlanes extends Page
{
    use CalendarioDeMantenciones;

    protected static string $resource = PlanMantenimientoResource::class;

    protected string $view = 'filament.resources.plan-mantenimientos.pages.calendario-planes';

    protected static ?string $title = 'Calendario de mantenciones';

    protected static ?string $breadcrumb = 'Calendario';

    #[Url]
    public int $anio;

    #[Url]
    public ?int $mes = null;

    #[Url]
    public ?string $servicio = null;

    #[Url]
    public ?string $criticidad = null;

    #[Url]
    public ?string $tipo = null;

    public static function canAccess(array $parameters = []): bool
    {
        return PlanMantenimientoResource::canViewAny();
    }

    public function mount(): void
    {
        $this->anio ??= now()->year;
    }

    public function updatedMes(): void
    {
        $this->mes = $this->mes !== null ? max(1, min(12, $this->mes)) : null;
    }

    public function anioCalendario(): int
    {
        return $this->anio;
    }

    public function puedeCambiarDeAnio(): bool
    {
        return true;
    }

    public function getSubheading(): ?string
    {
        return $this->mes === null
            ? 'Mantenciones preventivas del año por mes. Haz clic en un mes para verlo por días.'
            : 'Las realizadas aparecen en su fecha real. Las pendientes no tienen día: el plan programa por mes.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('listado')
                ->label('Ver listado')
                ->icon(Heroicon::OutlinedListBullet)
                ->color('gray')
                ->url(PlanMantenimientoResource::getUrl('index')),
            Action::make('crear')
                ->label('Crear plan de mantenimiento')
                ->icon(Heroicon::OutlinedPlus)
                ->url(PlanMantenimientoResource::getUrl('create'))
                ->visible(fn (): bool => PlanMantenimientoResource::canCreate()),
        ];
    }
}
