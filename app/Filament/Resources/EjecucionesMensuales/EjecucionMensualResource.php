<?php

namespace App\Filament\Resources\EjecucionesMensuales;

use App\Filament\Resources\EjecucionesMensuales\Pages\EditEjecucionMensual;
use App\Filament\Resources\EjecucionesMensuales\Pages\ListEjecucionesMensuales;
use App\Filament\Resources\EjecucionesMensuales\Schemas\EjecucionMensualForm;
use App\Filament\Resources\EjecucionesMensuales\Tables\EjecucionesMensualesTable;
use App\Models\EjecucionMensual;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EjecucionMensualResource extends Resource
{
    protected static ?string $model = EjecucionMensual::class;

    protected static ?string $slug = 'ejecuciones-mensuales';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Mantenimiento';

    protected static ?string $navigationLabel = 'Bitácora';

    protected static ?string $modelLabel = 'ejecución mensual';

    protected static ?string $pluralModelLabel = 'bitácora';

    /**
     * RF-49 (Módulo 12): oculta este recurso de la navegación — el punto de entrada canónico
     * para editar la bitácora es el RelationManager de PlanMantenimiento
     * (`EjecucionesMensualesRelationManager`), que protege RN-01 (invariante de 12 meses por plan).
     * Este recurso standalone queda disponible por URL pero no aparece en el menú lateral.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return EjecucionMensualForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EjecucionesMensualesTable::configure($table);
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
            'index' => ListEjecucionesMensuales::route('/'),
            'edit' => EditEjecucionMensual::route('/{record}/edit'),
        ];
    }
}
