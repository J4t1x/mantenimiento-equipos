<?php

namespace App\Models;

use App\Models\Contracts\AcotadoPorRecinto;
use App\Models\Scopes\AlcanceRecintoScope;
use Carbon\CarbonImmutable;
use Database\Factories\ProgramaAnualMantenimientoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * RF-72 del SRS (Res. Ex. 1341/2017 §7.2 y §7.3): el programa anual de MP de un establecimiento
 * se define a más tardar en marzo, en carta Gantt (la grilla de 12 meses de los planes), y lo
 * conoce y valida la Dirección. Aquí se registran ambos hitos; los campos de quién valida y con qué
 * documento son genéricos, a la espera de que el requirente confirme el procedimiento.
 */
#[Fillable([
    'recinto_id',
    'anio',
    'fecha_definicion',
    'fecha_validacion',
    'validado_por_nombre',
    'validado_por_cargo',
    'documento_validacion',
    'observaciones',
])]
#[ScopedBy([AlcanceRecintoScope::class])]
class ProgramaAnualMantenimiento extends Model implements AcotadoPorRecinto, AuditableContract
{
    /** @use HasFactory<ProgramaAnualMantenimientoFactory> */
    use Auditable, HasFactory;

    protected $table = 'programas_anuales_mantenimiento';

    protected function casts(): array
    {
        return [
            'fecha_definicion' => 'date',
            'fecha_validacion' => 'date',
        ];
    }

    /**
     * §7.2: "definido a más tardar en marzo de cada año".
     */
    public function definidoEnPlazo(): bool
    {
        return $this->fecha_definicion->lessThanOrEqualTo(CarbonImmutable::create($this->anio, 3, 31));
    }

    public function estaValidado(): bool
    {
        return $this->fecha_validacion !== null;
    }

    /**
     * Estado en una línea, para el informe de críticos.
     */
    public function descripcion(): string
    {
        $definicion = 'definido el '.$this->fecha_definicion->format('d-m-Y').($this->definidoEnPlazo() ? '' : ' (fuera del plazo de marzo)');

        if (! $this->estaValidado()) {
            return "{$definicion}; sin validación de la Dirección";
        }

        $validador = collect([$this->validado_por_nombre, filled($this->validado_por_cargo) ? "({$this->validado_por_cargo})" : null])->filter()->implode(' ');

        return collect([
            $definicion,
            'validado el '.$this->fecha_validacion->format('d-m-Y').($validador !== '' ? " por {$validador}" : ''),
            $this->documento_validacion,
        ])->filter()->implode('; ');
    }

    public function restringirARecintos(Builder $query, array $recintoIds): void
    {
        $query->whereIn($this->qualifyColumn('recinto_id'), $recintoIds);
    }

    public function recinto(): BelongsTo
    {
        return $this->belongsTo(Recinto::class);
    }
}
