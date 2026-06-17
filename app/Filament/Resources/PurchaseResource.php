<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Purchase;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $slug = 'purchases';
    protected static ?string $navigationLabel = 'Инвентарь';
    protected static ?string $pluralLabel = 'Инвентарь';
    protected static ?string $modelLabel = 'Объект';
    protected static ?string $navigationGroup = 'Справочники';
    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('supermanager');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('point_id')
                    ->required()
                    ->label('Точка')
                    ->relationship('point', 'name'),

                TextInput::make('name')
                    ->required()
                    ->label('Название'),

                TextInput::make('left_in_stock')
                    ->required()
                    ->numeric()
                    ->label('Осталось в наличии'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('point.name')
                    ->label('Точка продаж')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Название'),

                TextColumn::make('left_in_stock')
                    ->label('Остаток в наличии')
                    ->sortable()
                    ->searchable()
                    ->color(fn (string $state): string => $state <= 5 ? 'danger' : 'success'),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
