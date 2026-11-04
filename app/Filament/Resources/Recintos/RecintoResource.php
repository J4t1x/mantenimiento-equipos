<?php

namespace App\Filament\Resources\Recintos;

use App\Filament\Resources\Recintos\Pages\CreateRecinto;
use App\Filament\Resources\Recintos\Pages\EditRecinto;
use App\Filament\Resources\Recintos\Pages\ListRecintos;
use App\Filament\Resources\Recintos\Pages\ViewRecinto;
use App\Filament\Resources\Recintos\RelationManagers\PresupuestosMantenimientoRelationManager;
use App\Filament\Resources\Recintos\RelationManagers\ProgramasAnualesMantenimientoRelationManager;
use App\Filament\Resources\Recintos\Schemas\RecintoForm;
use App\Filament\Resources\Recintos\Schemas\RecintoInfolist;
use App\Filament\Resources\Recintos\Tables\RecintosTable;
use App\Filament\Support\EtiquetasEnEspanol;
use App\Models\Recinto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RecintoResource extends Resource
{
    use EtiquetasEnEspanol;

    protected static ?string $model = Recinto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $navigationLabel = 'Recintos';

    protected static ?string $modelLabel = 'recinto';

    protected static ?string $pluralModelLabel = 'recintos';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return RecintoForm::configure($schema);
    }

    /**
     * SPEC-ME-014: ficha del recinto, para quienes no administran el catálogo (encargado del
     * establecimiento, Jefatura).
     */
    public static function infolist(Schema $schema): Schema
    {
        return RecintoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecintosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProgramasAnualesMantenimientoRelationManager::class,
            PresupuestosMantenimientoRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecintos::route('/'),
            'create' => CreateRecinto::route('/create'),
            'view' => ViewRecinto::route('/{record}'),
            'edit' => EditRecinto::route('/{record}/edit'),
        ];
    }
}
