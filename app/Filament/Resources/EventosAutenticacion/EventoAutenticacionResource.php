<?php

namespace App\Filament\Resources\EventosAutenticacion;

use App\Filament\Resources\EventosAutenticacion\Pages\ListEventosAutenticacion;
use App\Filament\Resources\EventosAutenticacion\Tables\EventosAutenticacionTable;
use App\Filament\Support\EtiquetasEnEspanol;
use App\Models\EventoAutenticacion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * SPEC-ME-019 (RF-89, AUDIT §3.1): consulta del registro de accesos. Solo lectura: no hay
 * formularios ni acciones de edición o borrado.
 */
class EventoAutenticacionResource extends Resource
{
    use EtiquetasEnEspanol;

    protected static ?string $model = EventoAutenticacion::class;

    protected static ?string $slug = 'registro-de-accesos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Registro de accesos';

    protected static ?string $modelLabel = 'evento de acceso';

    protected static ?string $pluralModelLabel = 'registro de accesos';

    protected static ?int $navigationSort = 90;

    public static function table(Table $table): Table
    {
        return EventosAutenticacionTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventosAutenticacion::route('/'),
        ];
    }
}
