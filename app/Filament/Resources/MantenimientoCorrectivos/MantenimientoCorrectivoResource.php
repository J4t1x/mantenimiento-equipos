<?php

namespace App\Filament\Resources\MantenimientoCorrectivos;

use App\Filament\Resources\MantenimientoCorrectivos\Pages\CreateMantenimientoCorrectivo;
use App\Filament\Resources\MantenimientoCorrectivos\Pages\EditMantenimientoCorrectivo;
use App\Filament\Resources\MantenimientoCorrectivos\Pages\ListMantenimientoCorrectivos;
use App\Filament\Resources\MantenimientoCorrectivos\Pages\ViewMantenimientoCorrectivo;
use App\Filament\Resources\MantenimientoCorrectivos\Schemas\MantenimientoCorrectivoForm;
use App\Filament\Resources\MantenimientoCorrectivos\Schemas\MantenimientoCorrectivoInfolist;
use App\Filament\Resources\MantenimientoCorrectivos\Tables\MantenimientoCorrectivosTable;
use App\Models\MantenimientoCorrectivo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MantenimientoCorrectivoResource extends Resource
{
    protected static ?string $model = MantenimientoCorrectivo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'Mantenimiento';

    protected static ?string $navigationLabel = 'Correctivo';

    protected static ?string $modelLabel = 'mantenimiento correctivo';

    protected static ?string $pluralModelLabel = 'mantenimiento correctivo';

    public static function form(Schema $schema): Schema
    {
        return MantenimientoCorrectivoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MantenimientoCorrectivoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MantenimientoCorrectivosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMantenimientoCorrectivos::route('/'),
            'create' => CreateMantenimientoCorrectivo::route('/create'),
            'view' => ViewMantenimientoCorrectivo::route('/{record}'),
            'edit' => EditMantenimientoCorrectivo::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * RF-52 del SRS (Módulo 12): extiende la búsqueda global (RF-42) a Mantenimiento correctivo,
     * a través del nombre del equipo asociado.
     *
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['equipo.nombre'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "Correctivo de {$record->equipo->nombre} ({$record->fecha->format('d-m-Y')})";
    }
}
