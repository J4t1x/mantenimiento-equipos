<?php

namespace App\Filament\Resources\EventosAutenticacion\Tables;

use App\Enums\EventoAutenticacion;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventosAutenticacionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d-m-Y H:i:s')
                    ->sortable(),
                TextColumn::make('evento')
                    ->label('Evento')
                    ->badge(),
                TextColumn::make('email')
                    ->label('Correo escrito / usuario')
                    ->description(fn ($record): string => $record->user?->name ?? 'Sin cuenta')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label('Dirección IP')
                    ->searchable(),
                TextColumn::make('detalle')
                    ->label('Detalle')
                    ->state(fn ($record): ?string => self::detalle($record->detalle))
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('user_agent')
                    ->label('Navegador')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('evento')
                    ->label('Evento')
                    ->options(EventoAutenticacion::class)
                    ->multiple(),
                Filter::make('fecha')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, string $fecha) => $q->whereDate('created_at', '>=', $fecha))
                        ->when($data['hasta'] ?? null, fn (Builder $q, string $fecha) => $q->whereDate('created_at', '<=', $fecha))),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    /**
     * Traduce el detalle técnico a una frase legible.
     *
     * @param  array<string, mixed>|null  $detalle
     */
    private static function detalle(?array $detalle): ?string
    {
        if (blank($detalle)) {
            return null;
        }

        $partes = [];

        $partes[] = match ($detalle['motivo'] ?? null) {
            'cuenta_inexistente' => 'Correo no registrado',
            'contrasena_incorrecta' => 'Contraseña incorrecta',
            'cuenta_inactiva' => 'Cuenta inactiva',
            'acceso_bloqueado' => 'Intento durante un bloqueo',
            default => null,
        };

        if (isset($detalle['tipo'])) {
            $partes[] = ($detalle['tipo'] === 'ip' ? 'Por dirección IP' : 'Por cuenta').', '.($detalle['minutos'] ?? '?').' min';
        }

        $partes[] = match ($detalle['resultado'] ?? null) {
            'passwords.sent' => 'Enlace generado',
            'passwords.user' => 'Correo no registrado (no se envió)',
            'passwords.throttled' => 'Solicitud repetida (no se envió)',
            default => null,
        };

        if (isset($detalle['realizado_por'])) {
            $partes[] = 'Por '.$detalle['realizado_por'];
        }

        if (! empty($detalle['recordarme'])) {
            $partes[] = 'Con «Recordarme»';
        }

        return implode(' · ', array_filter($partes)) ?: null;
    }
}
