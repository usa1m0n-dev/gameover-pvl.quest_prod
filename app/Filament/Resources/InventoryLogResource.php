<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryLogResource\Pages;
use App\Models\Guest;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Point;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryLogResource extends Resource
{
    protected static ?string $model = InventoryLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Движение товара';
    protected static ?string $pluralLabel = 'Движение товара';
    protected static ?string $modelLabel = 'Операция';

    protected static ?string $navigationGroup = 'Справочники';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Детали операции')
                    ->schema([

                        // --- НОВОЕ ПОЛЕ: Выбор точки (Фильтр) ---
                        Forms\Components\Select::make('point_id')
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

                        // --- ИЗМЕНЕННОЕ ПОЛЕ: Выбор товара ---
                        Forms\Components\Select::make('inventory_id')
                            ->label('Товар')
                            // Динамическая загрузка опций
                            ->options(function (Get $get) {
                                $pointId = $get('point_id');

                                // Если точка не выбрана, возвращаем пустой список (или все, если хотите)
                                if (! $pointId) {
                                    return [];
                                    // Или Inventory::all()->pluck('name', 'id'); если хотите показывать все по умолчанию
                                }

                                // Возвращаем товары только этой точки
                                return Inventory::where('point_id', $pointId)->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                self::updateTotalPrice($set, $get);
                            }),

                        // ... Остальные поля без изменений
                        Forms\Components\Toggle::make('is_incoming')
                            ->label('Это приход?')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                self::updateTotalPrice($set, $get);
                            }),

                        Forms\Components\TextInput::make('amount')
                            ->label('Количество')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                self::updateTotalPrice($set, $get);
                            }),

                        Forms\Components\TextInput::make('price')
                            ->label('Сумма операции')
                            ->numeric()
                            ->prefix('₸')
                            ->required()
                            ->helperText('При расходе считается автоматически: Цена продажи * Кол-во'),

                        Forms\Components\Textarea::make('comment')
                            ->label('Комментарий')
                            ->columnSpanFull(),
                    ])->columns(2),

                // ... Секция "Субъект" остается без изменений
                Forms\Components\Section::make('Субъект')
                    ->schema([
                        Forms\Components\Toggle::make('by_employee')
                            ->label('Сотрудник?')
                            ->live()
                            ->default(false),

                        Forms\Components\Select::make('employee_id')
                            ->label('Сотрудник')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('by_employee'))
                            ->required(fn (Get $get) => $get('by_employee')),

                        Forms\Components\Select::make('guest_id')
                            ->label('Гость')
                            ->visible(fn (Get $get) => ! $get('by_employee'))
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Guest::query()
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (Guest $guest) => [$guest->id => "{$guest->name} ({$guest->phone})"])
                                ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => Guest::find($value)?->name),
                    ]),
            ]);
    }

    /**
     * Вспомогательная функция для расчета цены
     */
    protected static function updateTotalPrice(Set $set, Get $get): void
    {
        // Если это "Приход" (is_incoming = true), цену не трогаем (ее вводят вручную, т.к. цена закупа может меняться)
        if ($get('is_incoming')) {
            return;
        }

        $inventoryId = $get('inventory_id');
        $amount = (int) $get('amount');

        if ($inventoryId && $amount > 0) {
            // Находим товар и берем его цену продажи
            $inventory = Inventory::find($inventoryId);

            if ($inventory) {
                // Считаем: Цена товара * Количество
                $total = $inventory->sell_price * $amount;
                $set('price', $total);
            }
        }
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inventory.name')
                    ->label('Товар')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_incoming')
                    ->label('Тип')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-down-tray') // Иконка прихода
                    ->falseIcon('heroicon-o-arrow-up-tray')   // Иконка расхода
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Кол-во')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Сумма')
                    ->money('kzt'),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Кто')
                    ->getStateUsing(function (InventoryLog $record) {
                        return $record->by_employee
                            ? ($record->employee->name ?? 'Сотрудник удален')
                            : ($record->guest->name ?? 'Гость / Не указан');
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryLogs::route('/'),
            'create' => Pages\CreateInventoryLog::route('/create'),
            //'edit' => Pages\EditInventoryLog::route('/{record}'),
        ];
    }
}