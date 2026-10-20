<?php

namespace App\Filament\Support;

use App\Enums\Periodo;
use Livewire\Attributes\Reactive;

/**
 * Año y período elegidos en el Escritorio (`App\Filament\Pages\Escritorio`), recibidos como dos
 * escalares `#[Reactive]` vía `getWidgetData()`.
 *
 * Deben ser escalares y no un array: un array de filtros termina como atributo del placeholder del
 * widget y reproduce el error 500 de `HasFiltersForm` (`trim(): array given`, ver `Escritorio`).
 * Fuera del Escritorio (o sin valor) se usa el año en curso y el año completo.
 */
trait RecibePeriodoDelEscritorio
{
    #[Reactive]
    public ?int $anio = null;

    #[Reactive]
    public ?string $periodo = null;

    protected function anioSeleccionado(): int
    {
        return $this->anio ?? now()->year;
    }

    protected function periodoSeleccionado(): Periodo
    {
        return Periodo::tryFrom((string) $this->periodo) ?? Periodo::Anual;
    }

    protected function esAnioEnCurso(): bool
    {
        return $this->anioSeleccionado() === now()->year;
    }
}
