<?php

namespace App\Filament\Resources\ConvenioEjecucionesMensuales;

use App\Filament\Resources\ConvenioEjecucionesMensuales\Pages\CreateConvenioEjecucionMensual;
use App\Filament\Resources\ConvenioEjecucionesMensuales\Pages\EditConvenioEjecucionMensual;
use App\Filament\Resources\ConvenioEjecucionesMensuales\Pages\ListConvenioEjecucionesMensuales;
use App\Filament\Resources\ConvenioEjecucionesMensuales\Schemas\ConvenioEjecucionMensualForm;
use App\Filament\Resources\ConvenioEjecucionesMensuales\Tables\ConvenioEjecucionesMensualesTable;
use App\Models\ConvenioEjecucionMensual;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConvenioEjecucionMensualResource extends Resource
{
    protected static ?string $model = ConvenioEjecucionMensual::class;

    protected static ?string $slug = 'convenio-ejecuciones-mensuales';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Convenios y Gasto';

    protected static ?string $navigationLabel = 'Ejecución de gasto';

    protected static ?string $modelLabel = 'ejecución mensual de convenio';

    protected static ?string $pluralModelLabel = 'ejecución de gasto';

    /**
     * RF-49 (Módulo 12): oculta este recurso de la navegación — el punto de entrada canónico
     * para editar la ejecución mensual de convenio es el RelationManager de Convenios
     * (`EjecucionesMensualesRelationManager`). Este recurso standalone queda disponible por URL
     * pero no aparece en el menú lateral.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ConvenioEjecucionMensualForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConvenioEjecucionesMensualesTable::configure($table);
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
            'index' => ListConvenioEjecucionesMensuales::route('/'),
            'create' => CreateConvenioEjecucionMensual::route('/create'),
            'edit' => EditConvenioEjecucionMensual::route('/{record}/edit'),
        ];
    }
}
