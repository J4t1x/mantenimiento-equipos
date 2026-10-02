<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'activo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /** @var array<int, int>|null memo de {@see recintoIdsAsignados()} para esta instancia */
    private ?array $recintoIdsAsignados = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /**
     * RF-63: recintos asignados. Sin recintos, el usuario ve todos (subdepartamento del SSA).
     */
    public function recintos(): BelongsToMany
    {
        return $this->belongsToMany(Recinto::class);
    }

    /**
     * Ids de los recintos asignados, leídos directo de la tabla pivote: pasar por `recintos()`
     * aplicaría el global scope de `Recinto`, que a su vez depende de este mismo método.
     *
     * @return array<int, int>
     */
    public function recintoIdsAsignados(): array
    {
        return $this->recintoIdsAsignados ??= DB::table('recinto_user')
            ->where('user_id', $this->getKey())
            ->pluck('recinto_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function olvidarRecintosAsignados(): void
    {
        $this->recintoIdsAsignados = null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // `activo` solo tiene default (true) a nivel de columna: una instancia recién creada en
        // memoria sin recargar desde la BD (p. ej. `actingAs()` en tests) puede traerlo en null.
        return (bool) $this->activo;
    }
}
