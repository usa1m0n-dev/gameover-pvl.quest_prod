<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Сотрудники';
    protected static ?string $label = "Сотрудник";
    protected static ?string $pluralLabel = "Сотрудники";
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Справочники';
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('supermanager');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- СЕКЦИЯ 1: ЛИЧНЫЕ ДАННЫЕ ---
                Forms\Components\Section::make('Личные данные')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('ФИО')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('hire_date')
                            ->label('Дата найма')
                            ->required()
                            ->default(now()),

                        Forms\Components\TextInput::make('position')
                            ->label('Должность')
                            ->maxLength(255),
                    ]),

                // --- СЕКЦИЯ 2: ПЕРСОНАЛЬНЫЕ СТАВКИ ---
                Forms\Components\Section::make('Квалификация и Ставки')
                    ->description('Настройте персональные коэффициенты ЗП для игр. Если ставки нет — используется множитель 1.0 (База).')
                    ->schema([
                        Forms\Components\Repeater::make('rates')
                            ->relationship() // Используем связь rates() из модели
                            ->label('Список коэффициентов')
                            ->schema([
                                // Выбор игры
                                Forms\Components\Select::make('activity_id')
                                    ->label('Игра / Услуга')
                                    ->relationship('activity', 'name')
                                    ->required()
                                    // Магия: Не дает выбрать одну игру дважды для одного сотрудника
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                // Множитель
                                Forms\Components\TextInput::make('rate_multiplier')
                                    ->label('Коэффициент')
                                    ->numeric()
                                    ->default(1.0)
                                    ->step(0.1) // Шаг 0.1
                                    ->minValue(0)
                                    ->suffix('x')
                                    ->helperText('1.0 = Норма, 1.2 = +20%, 1.5 = +50%'),
                            ])
                            ->columns(2) // В 2 колонки внутри репитера
                            ->grid(2) // Сетка карточек (по 2 в ряд), чтобы экономить место
                            ->addActionLabel('Добавить спец. ставку')
                            ->itemLabel(fn (array $state): ?string =>
                                \App\Models\Activity::find($state['activity_id'] ?? null)?->name ?? null
                            ),
                    ]),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Имя'),
                Tables\Columns\TextColumn::make('phone')->label('Телефон'),

                // Расчетный баланс (Заработал - Получил)
                Tables\Columns\TextColumn::make('balance')
                    ->label('Долг компании')
                    ->money('kzt')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success') // Красный, если мы должны
                    ->getStateUsing(function ($record) {
                        return $record->balance; // Используем аксессор из модели
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // КНОПКА ВЫПЛАТЫ ПРЯМО В ТАБЛИЦЕ
                Tables\Actions\Action::make('payout')
                    ->label('Выплатить')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Сумма')
                            ->numeric()
                            ->required()
                            ->default(fn ($record) => $record->balance), // Подставляем весь долг

                        Forms\Components\DatePicker::make('date')
                            ->default(now())
                            ->required(),

                        Forms\Components\TextInput::make('comment')
                            ->label('Комментарий'),
                    ])
                    ->action(function (Employee $record, array $data) {
                        // Создаем запись о выплате
                        $record->payouts()->create([
                            'amount' => $data['amount'], // Мутатор сам умножит на 100
                            'date' => $data['date'],
                            'comment' => $data['comment'],
                        ]);

                        // Filament сам покажет уведомление
                        \Filament\Notifications\Notification::make()
                            ->title('Выплата проведена')
                            ->success()
                            ->send();
                    }),
            ]);
    }
    public static function getRelations(): array
    {
        return [
            // Добавляем менеджер выплат
            RelationManagers\PayoutsRelationManager::class,
            RelationManagers\RecordActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
