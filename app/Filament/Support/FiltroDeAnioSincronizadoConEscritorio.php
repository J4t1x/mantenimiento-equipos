<?php

namespace App\Filament\Support;

/**
 * Para los gráficos del Escritorio con filtro de año propio (`ChartWidget::$filter`): el filtro
 * parte en el año elegido en el Escritorio y lo sigue cuando ese año cambia. El usuario puede
 * igual cambiar el año solo en el gráfico; el Escritorio vuelve a mandar en su próximo cambio.
 */
trait FiltroDeAnioSincronizadoConEscritorio
{
    use RecibePeriodoDelEscritorio;

    /**
     * Último año del Escritorio aplicado al filtro, para detectar cuándo cambió.
     */
    public ?int $anioEscritorioAplicado = null;

    public function mount(): void
    {
        $this->sincronizarFiltroConEscritorio();

        parent::mount();
    }

    protected function sincronizarFiltroConEscritorio(): void
    {
        $anio = $this->anioSeleccionado();

        if ($this->filter === null || $this->anioEscritorioAplicado !== $anio) {
            $this->filter = (string) $anio;
            $this->anioEscritorioAplicado = $anio;
        }
    }

    /**
     * Años del filtro: los que tienen datos, el año en curso y el elegido en el Escritorio.
     *
     * @param  iterable<int|string>  $aniosConDatos
     * @return array<string, string>
     */
    protected function opcionesDeAnio(iterable $aniosConDatos): array
    {
        return collect($aniosConDatos)
            ->map(fn ($anio): int => (int) $anio)
            ->push(now()->year, $this->anioSeleccionado())
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn (int $anio): array => [(string) $anio => (string) $anio])
            ->all();
    }
}
