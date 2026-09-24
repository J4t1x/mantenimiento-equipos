<?php

namespace App\Filament\Resources\Recintos;

use App\Filament\Resources\Recintos\Pages\CreateRecinto;
use App\Filament\Resources\Recintos\Pages\EditRecinto;
use App\Filament\Resources\Recintos\Pages\ListRecintos;
use App\Filament\Resources\Recintos\Schemas\RecintoForm;
use App\Filament\Resources\Recintos\Tables\RecintosTable;
use App\Models\Recinto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RecintoResource extends Resource
{
    protected static ?string $model = Recinto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $navigationLabel = 'Recintos';

    protected static ?string $modelLabel = 'recinto';

    protected static ?string $pluralModelLabel = 'recintos';

    public static function form(Schema $schema): Schema
    {
        return RecintoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecintosTable::configure($table);
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
            'index' => ListRecintos::route('/'),
            'create' => CreateRecinto::route('/create'),
            'edit' => EditRecinto::route('/{record}/edit'),
        ];
    }
}
