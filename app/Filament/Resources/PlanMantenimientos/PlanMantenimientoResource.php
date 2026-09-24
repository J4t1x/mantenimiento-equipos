<?php

namespace App\Filament\Resources\PlanMantenimientos;

use App\Filament\Resources\PlanMantenimientos\Pages\CreatePlanMantenimiento;
use App\Filament\Resources\PlanMantenimientos\Pages\EditPlanMantenimiento;
use App\Filament\Resources\PlanMantenimientos\Pages\ListPlanMantenimientos;
use App\Filament\Resources\PlanMantenimientos\Pages\ViewPlanMantenimiento;
use App\Filament\Resources\PlanMantenimientos\RelationManagers\EjecucionesMensualesRelationManager;
use App\Filament\Resources\PlanMantenimientos\Schemas\PlanMantenimientoForm;
use App\Filament\Resources\PlanMantenimientos\Schemas\PlanMantenimientoInfolist;
use App\Filament\Resources\PlanMantenimientos\Tables\PlanMantenimientosTable;
use App\Models\PlanMantenimiento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanMantenimientoResource extends Resource
{
    protected static ?string $model = PlanMantenimiento::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Mantenimiento';

    protected static ?string $navigationLabel = 'Planificación MP';

    protected static ?string $modelLabel = 'plan de mantenimiento';

    protected static ?string $pluralModelLabel = 'planes de mantenimiento';

    public static function form(Schema $schema): Schema
    {
        return PlanMantenimientoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PlanMantenimientoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlanMantenimientosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EjecucionesMensualesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlanMantenimientos::route('/'),
            'create' => CreatePlanMantenimiento::route('/create'),
            'view' => ViewPlanMantenimiento::route('/{record}'),
            'edit' => EditPlanMantenimiento::route('/{record}/edit'),
        ];
    }

    /**
     * RF-50 del SRS (Módulo 12): mismo criterio de soft-delete que Equipo/Convenio/Mantenimiento
     * correctivo.
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * RF-52 del SRS (Módulo 12): extiende la búsqueda global (RF-42) a Plan de mantenimiento, a
     * través del nombre del equipo asociado (no tiene un campo "nombre" propio).
     *
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['equipo.nombre'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "Plan de {$record->equipo->nombre} ({$record->anio})";
    }
}
