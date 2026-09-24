<?php

namespace App\Models;

use App\Observers\ConvenioEjecucionMensualObserver;
use Database\Factories\ConvenioEjecucionMensualFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable(['convenio_id', 'anio', 'mes', 'n_orden_compra', 'monto'])]
#[ObservedBy(ConvenioEjecucionMensualObserver::class)]
class ConvenioEjecucionMensual extends Model implements AuditableContract
{
    /** @use HasFactory<ConvenioEjecucionMensualFactory> */
    use Auditable, HasFactory;

    protected $table = 'convenio_ejecuciones_mensuales';

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class);
    }
}
