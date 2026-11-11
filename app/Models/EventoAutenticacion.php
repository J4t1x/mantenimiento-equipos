<?php

namespace App\Models;

use App\Enums\EventoAutenticacion as TipoEvento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

/**
 * SPEC-ME-019 (RF-89, AUDIT §3.1): un evento de autenticación (quién, qué, cuándo y desde dónde).
 * El registro es de solo inserción: no se puede modificar ni eliminar desde la aplicación.
 *
 * @property TipoEvento $evento
 * @property array<string, mixed>|null $detalle
 */
class EventoAutenticacion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'eventos_autenticacion';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'evento' => TipoEvento::class,
            'detalle' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('El registro de accesos no se puede modificar.'));
        static::deleting(fn (): never => throw new LogicException('El registro de accesos no se puede eliminar.'));
    }

    /**
     * Registra un evento con la IP y el navegador de la solicitud en curso.
     *
     * @param  array<string, mixed>  $detalle
     */
    public static function registrar(TipoEvento $evento, ?User $usuario = null, ?string $email = null, array $detalle = []): self
    {
        $request = request();

        return static::query()->create([
            'user_id' => $usuario?->getKey(),
            'email' => Str::limit(mb_strtolower(trim((string) ($email ?? $usuario?->email))), 250, '') ?: null,
            'evento' => $evento,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, '') ?: null,
            'detalle' => $detalle ?: null,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
