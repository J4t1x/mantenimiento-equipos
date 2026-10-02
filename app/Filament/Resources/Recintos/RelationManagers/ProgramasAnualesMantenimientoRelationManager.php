<?php

namespace App\Filament\Resources\Recintos\RelationManagers;

use App\Models\ProgramaAnualMantenimiento;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

/**
 * RF-72 (Módulo 16, Res. Ex. 1341/2017 §7.2 y §7.3): definición (a más tardar en marzo) y
 * validación por la Dirección del programa anual de MP del recinto, uno por año.
 */
class ProgramasAnualesMantenimientoRelationManager extends RelationManager
{
    protected static string $relationship = 'programasAnualesMantenimiento';

    protected static ?string $title = 'Programa anual de MP';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('anio')
                    ->label('Año')
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->default(now()->year)
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('recinto_id', $this->getOwnerRecord()->getKey())),
                DatePicker::make('fecha_definicion')
                    ->label('Fecha de definición del programa')
                    ->helperText('La norma exige definirlo a más tardar en marzo.')
                    ->required(),
                DatePicker::make('fecha_validacion')
                    ->label('Fecha de validación por la Dirección')
                    ->afterOrEqual('fecha_definicion'),
                TextInput::make('documento_validacion')
                    ->label('N° de documento de validación')
                    ->placeholder('Ej.: Res. Ex. N° 1234')
                    ->maxLength(100),
                TextInput::make('validado_por_nombre')
                    ->label('Validado por (nombre)')
                    ->maxLength(150),
                TextInput::make('validado_por_cargo')
                    ->label('Cargo')
                    ->maxLength(150),
                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('anio')
            ->defaultSort('anio', 'desc')
            ->columns([
                TextColumn::make('anio')
                    ->label('Año'),
                TextColumn::make('fecha_definicion')
                    ->label('Definido')
                    ->date('d-m-Y')
                    ->color(fn (ProgramaAnualMantenimiento $record): ?string => $record->definidoEnPlazo() ? null : 'danger')
                    ->description(fn (ProgramaAnualMantenimiento $record): ?string => $record->definidoEnPlazo() ? null : 'Fuera del plazo de marzo'),
                TextColumn::make('fecha_validacion')
                    ->label('Validado por la Dirección')
                    ->date('d-m-Y')
                    ->placeholder('Sin validar')
                    ->description(fn (ProgramaAnualMantenimiento $record): ?string => $record->validado_por_nombre),
                TextColumn::make('documento_validacion')
                    ->label('Documento')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
