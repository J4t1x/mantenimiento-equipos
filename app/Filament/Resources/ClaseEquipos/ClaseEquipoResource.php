<?php

namespace App\Filament\Resources\ClaseEquipos;

use App\Filament\Resources\ClaseEquipos\Pages\CreateClaseEquipo;
use App\Filament\Resources\ClaseEquipos\Pages\EditClaseEquipo;
use App\Filament\Resources\ClaseEquipos\Pages\ListClaseEquipos;
use App\Filament\Resources\ClaseEquipos\Schemas\ClaseEquipoForm;
use App\Filament\Resources\ClaseEquipos\Tables\ClaseEquiposTable;
use App\Models\ClaseEquipo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClaseEquipoResource extends Resource
{
    protected static ?string $model = ClaseEquipo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $navigationLabel = 'Clases y subclases';

    protected static ?string $modelLabel = 'clase de equipo';

    protected static ?string $pluralModelLabel = 'clases y subclases';

    public static function form(Schema $schema): Schema
    {
        return ClaseEquipoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClaseEquiposTable::configure($table);
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
            'index' => ListClaseEquipos::route('/'),
            'create' => CreateClaseEquipo::route('/create'),
            'edit' => EditClaseEquipo::route('/{record}/edit'),
        ];
    }
}
