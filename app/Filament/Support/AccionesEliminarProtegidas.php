<?php

namespace App\Filament\Support;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * RF-04 del SRS: "impedir eliminar (solo desactivar) un valor de catálogo que esté referenciado
 * por al menos un equipo". La integridad ya la garantiza `restrictOnDelete()` en la base de datos
 * (MODELO-DATOS.md) — sin esta clase, el intento de borrado dejaba pasar el `QueryException` crudo
 * de PostgreSQL (SQLSTATE 23001) hasta el usuario, en vez de bloquearlo con un mensaje claro.
 *
 * `DB::transaction()` es necesario, no cosmético: en PostgreSQL, una sentencia fallida dentro de
 * una transacción la deja "abortada" — cualquier consulta posterior en esa misma transacción falla
 * también, aunque se haya capturado la excepción — hasta que se haga `ROLLBACK`. Al envolver el
 * borrado en su propia transacción (usa un `SAVEPOINT` si ya hay una transacción abierta alrededor,
 * como en tests con `RefreshDatabase`), Laravel hace ese rollback automáticamente al relanzar la
 * excepción, dejando la conexión sana para que Filament pueda re-renderizar la tabla.
 *
 * Reutilizable en los 4 catálogos (Recintos, Servicios Clínicos, Clases de Equipo, Proveedores).
 */
class AccionesEliminarProtegidas
{
    /**
     * Códigos SQLSTATE de violación de integridad referencial en PostgreSQL: 23001 (RESTRICT) y
     * 23503 (FOREIGN KEY, por si algún catálogo cambia a esa variante a futuro).
     */
    private const SQLSTATES_VIOLACION_REFERENCIAL = ['23001', '23503'];

    public static function individual(): DeleteAction
    {
        return DeleteAction::make()
            ->action(function (Model $record, DeleteAction $action): void {
                try {
                    DB::transaction(fn () => $record->delete());
                } catch (QueryException $exception) {
                    self::manejarSiEsReferencial($exception, $action);
                }
            });
    }

    public static function enLote(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->action(function (Collection $records, DeleteBulkAction $action): void {
                try {
                    DB::transaction(fn () => $records->each->delete());
                } catch (QueryException $exception) {
                    self::manejarSiEsReferencial($exception, $action);
                }
            });
    }

    private static function manejarSiEsReferencial(QueryException $exception, DeleteAction|DeleteBulkAction $action): void
    {
        if (! in_array($exception->getCode(), self::SQLSTATES_VIOLACION_REFERENCIAL, true)) {
            throw $exception;
        }

        $notification = Notification::make()
            ->danger()
            ->title('No se puede eliminar')
            ->body('Tiene equipos asociados en el catastro. Desactívalo en vez de eliminarlo (RF-04).');

        $notification->send();

        // RF-47 (Módulo 12): además del toast, se persiste a la campana de notificaciones —
        // hoy se pierde si el usuario no está mirando la pantalla exacta en el momento del rechazo.
        if ($usuario = auth()->user()) {
            $notification->sendToDatabase($usuario);
        }

        $action->halt();
    }
}
