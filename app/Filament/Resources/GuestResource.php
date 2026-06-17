<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuestResource\Pages;
use App\Filament\Resources\GuestResource\RelationManagers;
use App\Models\Guest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GuestResource extends Resource
{
    protected static ?string $model = Guest::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Гости';
    protected static ?string $label = "Гость";
    protected static ?string $pluralLabel = "Гости";
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Справочники';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Имя')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('birthday')
                            ->label('Дата рождения'),
                    ])->columns(2),

                // Новая секция для NFC брелоков
                Forms\Components\Section::make('NFC Доступ и скидки')
                    ->description('Управление персональным брелоком гостя')
                    ->schema([
                        Forms\Components\TextInput::make('keychain_hwid')
                            ->label('ID Брелока')
                            ->disabled() // Делает поле неизменяемым
                            ->placeholder('Брелок не привязан')
                            ->hintAction(
                                Forms\Components\Actions\Action::make('bind_keychain')
                                    ->label('Привязать брелок')
                                    ->icon('heroicon-m-signal')
                                    // Кнопка перенаправляет на твой роут.
                                    ->url(fn (?Guest $record) => $record ? url('/make_keychain_first/' . $record->id) : null)
                                    // Показываем кнопку ТОЛЬКО если гость уже создан (есть ID)
                                    ->visible(fn (?Guest $record) => $record !== null)
                            ),

                        Forms\Components\TextInput::make('keychain_personal_discount')
                            ->label('Персональная скидка')
                            ->numeric() // Только числа
                            ->step(1) // Шаг 1
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%') // Визуальный суффикс процента
                            ->default(0),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('birthday')
                    ->label('ДР')
                    ->date("d.m")
                    ->sortable()
                    ->searchable(),

                // Добавил отображение полей в таблице для удобства
                Tables\Columns\TextColumn::make('keychain_hwid')
                    ->label('NFC Брелок')
                    // Подменяем реальный HWID на текст "Есть" или "Нет"
                    ->getStateUsing(fn ($record) => $record->keychain_hwid ? 'Есть' : 'Нет')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Есть' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Кнопка привязки прямо из таблицы
                Tables\Actions\Action::make('bind_keychain')
                    ->label('Брелок')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->url(fn (Guest $record): string => url('/make_keychain_first/' . $record->id)),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RecordsRelationManagerRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuests::route('/'),
            'create' => Pages\CreateGuest::route('/create'),
            'edit' => Pages\EditGuest::route('/{record}/edit'),
        ];
    }
}