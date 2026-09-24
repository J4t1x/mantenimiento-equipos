<?php

namespace App\Models;

use App\Enums\EstadoEjecucion;
use App\Exceptions\EquipoNoAsignadoException;
use App\Exceptions\TransicionBitacoraInvalidaException;
use App\Support\AlcanceTecnicoInterno;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable(['plan_mantenimiento_id', 'mes', 'estado', 'fecha_real', 'observaciones'])]
class EjecucionMensual extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'ejecuciones_mensuales';

    protected function casts(): array
    {
        return [
            'estado' => EstadoEjecucion::class,
            'fecha_real' => 'date',
        ];
    }

    public function planMantenimiento(): BelongsTo
    {
        return $this->belongsTo(PlanMantenimiento::class);
    }

    /**
     * RN-02 (RNF-04, defensa en profundidad): guarda de última instancia a nivel de modelo, para
     * que la transición inválida se rechace sin importar la vía de entrada (Filament, tinker,
     * seeder, futura API) — no solo desde el formulario.
     */
    protected static function booted(): void
    {
        static::saving(function (self $ejecucion): void {
            if (! $ejecucion->isDirty('estado')) {
                return;
            }

            $estadoAnterior = $ejecucion->exists
                ? EstadoEjecucion::tryFrom((string) $ejecucion->getRawOriginal('estado'))
                : null;

            if (! EstadoEjecucion::esTransicionValida($estadoAnterior, $ejecucion->estado)) {
                throw TransicionBitacoraInvalidaException::porFaltaDeProgramacion();
            }
        });

        static::saving(function (self $ejecucion): void {
            if (! AlcanceTecnicoInterno::puedeGestionarPlan($ejecucion->planMantenimiento)) {
                throw EquipoNoAsignadoException::porFaltaDeAsignacion();
            }
        });
    }
}
