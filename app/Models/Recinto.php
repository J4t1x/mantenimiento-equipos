<?php

namespace App\Models;

use App\Models\Contracts\AcotadoPorRecinto;
use App\Models\Scopes\AlcanceRecintoScope;
use Database\Factories\RecintoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nombre',
    'responsable_mp_nombre',
    'responsable_mp_cargo',
    'responsable_mp_documento',
    'responsable_mp_fecha_designacion',
    'activo',
])]
#[ScopedBy([AlcanceRecintoScope::class])]
class Recinto extends Model implements AcotadoPorRecinto
{
    /** @use HasFactory<RecintoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'responsable_mp_fecha_designacion' => 'date',
        ];
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    /**
     * RF-76: gasto programado anual de MP y MC, por año.
     */
    public function presupuestosMantenimiento(): HasMany
    {
        return $this->hasMany(PresupuestoMantenimiento::class);
    }

    /**
     * RF-72: definición y validación del programa anual de MP, por año.
     */
    public function programasAnualesMantenimiento(): HasMany
    {
        return $this->hasMany(ProgramaAnualMantenimiento::class);
    }

    /**
     * RF-72 (Res. Ex. 1341/2017 §7.3): recintos activos sin programa del año validado por la
     * Dirección.
     */
    #[Scope]
    protected function sinProgramaValidado(Builder $query, ?int $anio = null): void
    {
        $query
            ->where($this->qualifyColumn('activo'), true)
            ->whereDoesntHave('programasAnualesMantenimiento', fn (Builder $programa) => $programa
                ->where('anio', $anio ?? now()->year)
                ->whereNotNull('fecha_validacion'));
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * RF-70: responsable de MP en una línea (p. ej. para el informe de críticos), o `null` si el
     * recinto todavía no tiene uno designado.
     */
    public function descripcionResponsableMp(): ?string
    {
        if (blank($this->responsable_mp_nombre)) {
            return null;
        }

        $designacion = collect([
            $this->responsable_mp_documento,
            $this->responsable_mp_fecha_designacion?->format('d-m-Y'),
        ])->filter()->implode(', ');

        return collect([
            $this->responsable_mp_nombre,
            filled($this->responsable_mp_cargo) ? "({$this->responsable_mp_cargo})" : null,
            $designacion !== '' ? "— designación: {$designacion}" : null,
        ])->filter()->implode(' ');
    }

    public function restringirARecintos(Builder $query, array $recintoIds): void
    {
        $query->whereIn($this->qualifyColumn('id'), $recintoIds);
    }
}
