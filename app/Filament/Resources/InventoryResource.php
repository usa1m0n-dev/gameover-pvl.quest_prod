<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Filament\Resources\InventoryResource\RelationManagers;
use App\Models\Inventory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Склад';
    protected static ?string $pluralLabel = 'Склад';
    protected static ?string $modelLabel = 'Товар';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationGroup = 'Справочники';
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('manager');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('point_id')
                    ->label('Точка продаж')
                    ->relationship('point', 'name'),

                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('sell_price')
                    ->label('Цена продажи')
                    ->numeric()
                    ->prefix('₸') // Или ваш символ валюты
                    ->default(0)
                    ->required(),

                Forms\Components\TextInput::make('amount')
                    ->label('Остаток')
                    ->numeric()
                    ->default(0)
                    ->helperText('Рекомендуется изменять остаток через создание лога, а не вручную.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('point.name')
                ->label('Точка'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),

                Tables\Columns\TextColumn::make('sell_price')
                    ->label('Цена')
                    ->money('kzt') // Форматирование денег
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Остаток')
                    ->numeric()
                    ->sortable()
                    ->color(fn (string $state): string => $state <= 5 ? 'danger' : 'success'), // Подсветка если мало

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(), // Фильтр для удаленных (SoftDeletes)
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Сюда можно добавить RelationManager для просмотра логов внутри товара
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }
}
