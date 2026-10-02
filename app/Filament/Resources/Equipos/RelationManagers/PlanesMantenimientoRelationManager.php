<?php

namespace App\Filament\Resources\Equipos\RelationManagers;

use App\Enums\FrecuenciaAnual;
use App\Enums\TipoMantenimiento;
use App\Rules\FrecuenciaMinimaSegunCriticidad;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlanesMantenimientoRelationManager extends RelationManager
{
    protected static string $relationship = 'planesMantenimiento';

    protected static ?string $title = 'Planes de mantenimiento';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('anio')
                    ->label('Año')
                    ->numeric()
                    ->required(),
                Select::make('frecuencia_anual')
                    ->label('Frecuencia anual')
                    ->options(FrecuenciaAnual::class)
                    ->required()
                    ->helperText('Los equipos críticos requieren al menos 2 mantenciones al año, salvo con garantía vigente, en que se puede usar la periodicidad del fabricante (Res. Ex. 1341/2017).')
                    ->rules([
                        fn ($get): FrecuenciaMinimaSegunCriticidad => new FrecuenciaMinimaSegunCriticidad(
                            $this->getOwnerRecord(),
                            filled($get('anio')) ? (int) $get('anio') : null,
                        ),
                    ]),
                Select::make('tipo_mantenimiento')
                    ->label('Tipo de mantenimiento')
                    ->options(TipoMantenimiento::class)
                    ->required(),
                Select::make('proveedor_id')
                    ->relationship('proveedor', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Proveedor'),
                Select::make('responsable_interno_id')
                    ->relationship('responsableInterno', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Responsable interno'),
                Select::make('convenio_id')
                    ->relationship('convenio', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Convenio'),
                TextInput::make('costo_anual_referencia')
                    ->label('Costo anual de referencia')
                    ->numeric()
                    ->prefix('$'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('anio')
            ->columns([
                TextColumn::make('anio')
                    ->label('Año')
                    ->sortable(),
                TextColumn::make('frecuencia_anual')
                    ->label('Frecuencia')
                    ->badge(),
                TextColumn::make('tipo_mantenimiento')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->placeholder('—'),
                TextColumn::make('convenio.nombre')
                    ->label('Convenio')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
