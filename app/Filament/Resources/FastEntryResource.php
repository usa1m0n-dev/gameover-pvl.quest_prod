<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FastEntryResource\Pages;
use App\Models\FastEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FastEntryResource extends Resource
{
    protected static ?string $model = FastEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Список записей';
    protected static ?string $pluralModelLabel = 'Записи быстрого ввода';
    protected static ?string $modelLabel = 'Запись';

    // Группировка в меню (опционально, если нужно объединить разделы)
    protected static ?string $navigationGroup = 'Клиенты';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о записи')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Имя')
                            ->placeholder('—')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->required()
                            ->tel(),

                        Forms\Components\DatePicker::make('visit_date')
                            ->label('Дата посещения'),

                        Forms\Components\Select::make('user_id')
                            ->relationship('operator', 'name')
                            ->label('Внес оператор')
                            ->searchable()
                            ->preload()
                            ->disabled(), // Запрещаем менять оператора вручную для честности логов
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable() // Оператор может скопировать номер кликом по нему
                    ->copyMessage('Номер скопирован в буфер')
                    ->sortable(),

                TextColumn::make('visit_date')
                    ->label('Дата посещения')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('operator.name')
                    ->label('Оператор')
                    ->placeholder('Система')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Время создания')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            // Новые записи всегда будут в самом верху списка
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Фильтр по оператору, занесшему данные
                SelectFilter::make('user_id')
                    ->relationship('operator', 'name')
                    ->label('По оператору')
                    ->searchable()
                    ->preload(),

                // Удобный фильтр по диапазону дат посещения
                Filter::make('visit_date')
                    ->form([
                        Forms\Components\DatePicker::make('visit_from')->label('Дата посещения с'),
                        Forms\Components\DatePicker::make('visit_until')->label('Дата посещения по'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['visit_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '>=', $date),
                            )
                            ->when(
                                $data['visit_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            // Здесь при необходимости можно подключить связи
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFastEntries::route('/'),
            'create' => Pages\CreateFastEntry::route('/create'),
            'edit' => Pages\EditFastEntry::route('/{record}/edit'),
        ];
    }
}