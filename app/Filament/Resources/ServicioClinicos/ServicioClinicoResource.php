<?php

namespace App\Filament\Resources\ServicioClinicos;

use App\Filament\Resources\ServicioClinicos\Pages\CreateServicioClinico;
use App\Filament\Resources\ServicioClinicos\Pages\EditServicioClinico;
use App\Filament\Resources\ServicioClinicos\Pages\ListServicioClinicos;
use App\Filament\Resources\ServicioClinicos\Schemas\ServicioClinicoForm;
use App\Filament\Resources\ServicioClinicos\Tables\ServicioClinicosTable;
use App\Models\ServicioClinico;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ServicioClinicoResource extends Resource
{
    protected static ?string $model = ServicioClinico::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|\UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $navigationLabel = 'Servicios clínicos';

    protected static ?string $modelLabel = 'servicio clínico';

    protected static ?string $pluralModelLabel = 'servicios clínicos';

    public static function form(Schema $schema): Schema
    {
        return ServicioClinicoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServicioClinicosTable::configure($table);
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
            'index' => ListServicioClinicos::route('/'),
            'create' => CreateServicioClinico::route('/create'),
            'edit' => EditServicioClinico::route('/{record}/edit'),
        ];
    }
}
