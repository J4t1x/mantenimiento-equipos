<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

/**
 * RF-53 (Módulo 12): botón de exportación a Excel sobre el resultado filtrado/buscado en pantalla
 * de un listado — distinto de los 3 reportes anuales fijos de la página Reportes (RF-31 a RF-33),
 * que exportan el año completo sin importar filtros. Reutilizable en los 3 recursos que lo piden
 * (Equipos, Convenios, Mantenimiento correctivo).
 *
 * `HasTable::getTableQueryForExport()` —a diferencia de `getFilteredSortedTableQuery()`, pensado
 * para paginar la tabla en pantalla— ya aplica filtros, búsqueda y orden sin paginar: exactamente
 * lo que pide el RF, sin tener que replicar esa lógica a mano.
 */
class AccionExportarTablaFiltrada
{
    /**
     * @param  callable(Builder): object  $exportable  arma el objeto `Maatwebsite\Excel` a partir
     *                                                 de la consulta ya filtrada/ordenada.
     */
    public static function make(string $nombreArchivo, callable $exportable): Action
    {
        return Action::make('exportarFiltrado')
            ->label('Exportar a Excel')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->action(fn (HasTable $livewire) => Excel::download(
                $exportable($livewire->getTableQueryForExport()),
                $nombreArchivo,
            ));
    }
}
