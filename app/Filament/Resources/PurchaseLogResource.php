<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseLogResource\Pages;
use App\Models\Inventory;
use App\Models\Point;
use App\Models\Purchase;
use App\Models\PurchaseLog;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PurchaseLogResource extends Resource
{
    protected static ?string $model = PurchaseLog::class;

    protected static ?string $slug = 'purchase-logs';
    protected static ?string $navigationLabel = 'Закуп';
    protected static ?string $pluralLabel = 'Закуп';
    protected static ?string $modelLabel = 'Операция';
    protected static ?string $navigationGroup = 'Справочники';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('manager');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make("Выбор Обьекта")->schema([
                    Select::make('point_id')
                    ->label('Точка (Фильтр)')
                    ->options(Point::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live() // Важно: обновляет форму при изменении
                    ->dehydrated(false) // Важно: не пытается сохранить это поле в БД (так как в логах нет point_id)
                    ->afterStateUpdated(function (Set $set) {
                        // Сбрасываем выбранный товар при смене точки
                        $set('inventory_id', null);
                        $set('price', null);
                    }),
                Select::make('purchase_id')
                    ->label('Объект')
                    ->options(function (Get $get) {
                        $pointId = $get('point_id');
                        if (! $pointId) return [];
                        return Purchase::where('point_id', $pointId)->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->live(),
                ]),
                Section::make("Детали операции")->schema([
                Toggle::make('is_expense')
                    ->label('Это расход?')
                    ->onColor('danger')
                    ->offColor('success')
                    ->default(true)
                    ->live(),

                TextInput::make('quantity')
                    ->label('Количество')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->default(1),

                TextInput::make('price_per_unit')
                    ->label('Цена за ед.')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->default(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchase.name')
                    ->label('Объект')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_expense')
                    ->label('Тип')
                    ->boolean()
                    ->falseIcon('heroicon-o-arrow-down-tray') // Иконка прихода
                    ->trueIcon('heroicon-o-arrow-up-tray')   // Иконка расхода
                    ->falseColor('success')
                    ->trueColor('danger'),

                TextColumn::make('quantity')->label('Количество'),

                TextColumn::make('price_per_unit')->label('Цена за ед.'),
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
            'index' => Pages\ListPurchaseLogs::route('/'),
            'create' => Pages\CreatePurchaseLog::route('/create'),
            'edit' => Pages\EditPurchaseLog::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['purchase']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['purchase.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->purchase) {
            $details['Purchase'] = $record->purchase->name;
        }

        return $details;
    }
}
