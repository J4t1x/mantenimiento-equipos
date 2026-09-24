<?php

namespace App\Filament\Resources\Convenios;

use App\Filament\Resources\Convenios\Pages\CreateConvenio;
use App\Filament\Resources\Convenios\Pages\EditConvenio;
use App\Filament\Resources\Convenios\Pages\ListConvenios;
use App\Filament\Resources\Convenios\Pages\ViewConvenio;
use App\Filament\Resources\Convenios\RelationManagers\EjecucionesMensualesRelationManager;
use App\Filament\Resources\Convenios\Schemas\ConvenioForm;
use App\Filament\Resources\Convenios\Schemas\ConvenioInfolist;
use App\Filament\Resources\Convenios\Tables\ConveniosTable;
use App\Models\Convenio;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConvenioResource extends Resource
{
    protected static ?string $model = Convenio::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Convenios y Gasto';

    protected static ?string $navigationLabel = 'Convenios';

    protected static ?string $modelLabel = 'convenio';

    protected static ?string $pluralModelLabel = 'convenios';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return ConvenioForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ConvenioInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConveniosTable::configure($table);
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
            'index' => ListConvenios::route('/'),
            'create' => CreateConvenio::route('/create'),
            'view' => ViewConvenio::route('/{record}'),
            'edit' => EditConvenio::route('/{record}/edit'),
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
     * RF-42 del SRS: búsqueda global (barra superior) sobre el nombre del convenio.
     *
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre'];
    }
}
