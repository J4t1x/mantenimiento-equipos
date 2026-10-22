<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ConvenioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable([
    'proveedor_id',
    'nombre',
    'n_resolucion',
    'fecha_resolucion',
    'fecha_expiracion',
    'monto_anual',
    'subasignacion_sigfe',
    'activo',
])]
class Convenio extends Model implements AuditableContract
{
    /** @use HasFactory<ConvenioFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'fecha_resolucion' => 'date',
            'fecha_expiracion' => 'date',
            'monto_anual' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * RF-86 (Módulo 19): un convenio que vence dentro de estos días se marca "Vence en N días". Es
     * el mismo umbral de la alerta "Convenios por vencer" del Escritorio (RF-43).
     */
    public const DIAS_POR_VENCER = 60;

    /**
     * RF-86: vigencia como estado, para el listado y su exportación: "Vencido", "Vence hoy",
     * "Vence en N días" (dentro de {@see self::DIAS_POR_VENCER}) o "Vigente".
     *
     * @return array{etiqueta: string, color: string}
     */
    public function estadoVigencia(?CarbonInterface $hoy = null): array
    {
        if ($this->fecha_expiracion === null) {
            return ['etiqueta' => 'Sin fecha', 'color' => 'gray'];
        }

        $hoy = ($hoy ?? now())->toImmutable()->startOfDay();
        $dias = (int) $hoy->diffInDays($this->fecha_expiracion->toImmutable()->startOfDay(), false);

        return match (true) {
            $dias < 0 => ['etiqueta' => 'Vencido', 'color' => 'danger'],
            $dias === 0 => ['etiqueta' => 'Vence hoy', 'color' => 'warning'],
            $dias <= self::DIAS_POR_VENCER => ['etiqueta' => $dias === 1 ? 'Vence en 1 día' : "Vence en {$dias} días", 'color' => 'warning'],
            default => ['etiqueta' => 'Vigente', 'color' => 'success'],
        };
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function planesMantenimiento(): HasMany
    {
        return $this->hasMany(PlanMantenimiento::class);
    }

    public function ejecucionesMensuales(): HasMany
    {
        return $this->hasMany(ConvenioEjecucionMensual::class);
    }
}
