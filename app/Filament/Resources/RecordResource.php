<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecordResource\Pages;
use App\Models\Activity;
use App\Models\PartyRoom;
use App\Models\Record;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Propaganistas\LaravelPhone\Rules\Phone;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class RecordResource extends Resource
{
    protected static ?string $model = Record::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Записи';
    protected static ?string $label = "Запись";
    protected static ?string $pluralLabel = "Записи";
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        // --- КАЛЬКУЛЯТОР (Без изменений) ---
        $calculateTotals = function (Get $get, Set $set) {
            $total = 0;
            $roomId = $get('room_id');
            $hours = (float) $get('estimated_room_time');

            if ($roomId && $hours > 0) {
                $room = PartyRoom::find($roomId);
                if ($room) {
                    $total += ($room->price_per_hour * $hours);
                }
            }

            $activities = $get('recordActivities');
            if (is_array($activities)) {
                foreach ($activities as $item) {
                    $price = (float) ($item['fixed_price'] ?? 0);
                    $players = (int) ($item['players_count'] ?? 0);
                    $discountPercent = (float) ($item['discount_multiplier'] ?? 0);
                    $multiplier = 1 - ($discountPercent / 100);
                    $total += ($price * $players * $multiplier);
                }
            }
            $set('total', $total);
        };

        // --- ЛОГИКА СЛОТОВ (НОВАЯ) ---
        //        $getAvailableTimes = function (Get $get) {
        //            $date = $get('date_only');
        //            $pointId = $get('point_id');
        //
        //            if (!$date || !$pointId) return [];
        //
        //            $baseSlots = [
        //                "00:00", "00:30", "01:00", "01:30", "02:00", "02:30", "03:00",
        //                "09:00", "09:30", "10:00", "10:30", "11:00", "11:30",
        //                "12:00", "12:30", "13:00", "13:30", "14:00", "14:30",
        //                "15:00", "15:30", "16:00", "16:30", "17:00", "17:30",
        //                "18:00", "18:30", "19:00", "19:30", "20:00", "20:30",
        //                "21:00", "21:30", "22:00", "22:30", "23:00", "23:30"
        //            ];
        //
        //            $currentRecordId = $get('id');
        //
        //            $busyRecords = Record::query()
        //                ->whereDate('datetime', $date)
        //                ->where('point_id', $pointId)
        //                ->when($currentRecordId, fn($q) => $q->where('id', '!=', $currentRecordId))
        //                ->get();
        //
        //            $options = [];
        //
        //            foreach ($baseSlots as $slot) {
        //                $slotTime = Carbon::parse($date . ' ' . $slot);
        //                $status = 'ok';
        //                $extraInfo = ''; // Текст про комнату
        //
        //                foreach ($busyRecords as $record) {
        //                    $gameStart = Carbon::parse($record->datetime);
        //                    $gameEnd = $gameStart->copy()->addMinutes(60);
        //
        //                    // 1. ПРОВЕРКА "ЗАНЯТО" (⛔)
        //                    if ($slotTime->greaterThanOrEqualTo($gameStart) && $slotTime->lessThan($gameEnd)) {
        //                        $status = 'busy';
        //
        //                        // ИСПРАВЛЕНИЕ: Пишем текст ТОЛЬКО если это слот начала игры (12:00),
        //                        // а не промежуточный (12:30)
        //                        if ($slotTime->equalTo($gameStart) && $record->room_id > 0) {
        //                            $extraInfo = " (+VIP {$record->estimated_room_time}ч)";
        //                        } else {
        //                            $extraInfo = ''; // Для 12:30 будет просто значок ⛔ без текста
        //                        }
        //
        //                        break;
        //                    }
        //
        //                    // 2. ПРОВЕРКА "БУФЕР" (⚠️)
        //                    $diffMinutes = abs($slotTime->diffInMinutes($gameStart));
        //
        //                    if ($diffMinutes < 90) {
        //                        if ($status !== 'busy') {
        //                            $status = 'warning';
        //                            // ИЗМЕНЕНИЕ: В буферной зоне мы НЕ пишем про VIP
        //                            $extraInfo = '';
        //                        }
        //                    }
        //                }
        //
        //                // Формируем красивый лейбл
        //                if ($status === 'busy') {
        //                    // Тут будет: "14:00 ⛔ (+VIP 3ч)" или просто "14:00 ⛔"
        //                    $options[$slot] = "$slot ⛔" . $extraInfo;
        //                } elseif ($status === 'warning') {
        //                    // Тут будет просто: "15:00 ⚠️" (без текста)
        //                    $options[$slot] = "$slot ⚠️";
        //                } else {
        //                    $options[$slot] = $slot;
        //                }
        //            }
        //
        //            return $options;
        //        };

        // 1. ФУНКЦИЯ ПРОВЕРКИ СТАТУСА СЛОТА (ИСПРАВЛЕННАЯ ЛОГИКА)
        $checkSlotStatus = function ($date, $pointId, $timeToCheck, $currentRecordId = null) {
            if (!$date || !$pointId || !$timeToCheck) return ['status' => 'ok', 'info' => ''];

            $slotTime = Carbon::parse($date . ' ' . $timeToCheck);

            $busyRecords = \App\Models\Record::query()
                ->whereDate('datetime', $date)
                ->where('point_id', $pointId)
                ->when($currentRecordId, fn($q) => $q->where('id', '!=', $currentRecordId))
                ->where('status', '!=', 'Отменена')
                ->get();

            // Переменные для хранения "наихудшего" статуса
            $finalStatus = 'ok';
            $finalInfo = '';

            foreach ($busyRecords as $record) {
                $gameStart = Carbon::parse($record->datetime);
                $gameEnd = $gameStart->copy()->addMinutes(60);

                // А. ПРОВЕРКА НА ПЕРЕСЕЧЕНИЕ (Самый высокий приоритет)
                if ($slotTime->greaterThanOrEqualTo($gameStart) && $slotTime->lessThan($gameEnd)) {
                    $finalStatus = 'busy';

                    // Формируем текст
                    if ($slotTime->equalTo($gameStart) && $record->room_id) {
                        $hours = $record->estimated_room_time > 0 ? " {$record->estimated_room_time}ч" : "";
                        $finalInfo = " ({$record->room->name} - {$hours})";
                    }

                    // Если нашли Busy - дальше можно не искать, хуже уже не будет. Выходим.
                    break;
                }

                // Б. ПРОВЕРКА НА БУФЕР (Средний приоритет)
                // Проверяем буфер, ТОЛЬКО если мы еще не нашли статус 'busy'
                if ($finalStatus !== 'busy') {
                    $diffMinutes = abs($slotTime->diffInMinutes($gameStart));
                    if ($diffMinutes < 90) {
                        $finalStatus = 'warning';
                        // Не делаем break! Вдруг следующая запись в цикле даст статус 'busy'?
                        // Мы должны продолжить проверку.
                    }
                }
            }

            return ['status' => $finalStatus, 'info' => $finalInfo];
        };
        // 2. ГЕНЕРАТОР ОПЦИЙ ДЛЯ КНОПОК (Только твоя спец. сетка)
        // 2. ГЕНЕРАТОР ОПЦИЙ ДЛЯ КНОПОК
        $getGridOptions = function (Get $get) use ($checkSlotStatus) {
            $date = $get('date_only');
            $pointId = $get('point_id');
            $currentId = $get('id');

            if (!$date || !$pointId) return [];

            // 1. ТВОЯ СТАНДАРТНАЯ СЕТКА
            $baseSlots = [
                '11:00',
                '12:00',
                '13:30',
                '14:00',
                '15:30',
                '16:00',
                '17:00',
                '17:30',
                '18:30',
                '19:00',
                '20:30',
                '22:00'
            ];

            // 2. ПОЛУЧАЕМ ВРЕМЯ УЖЕ СУЩЕСТВУЮЩИХ ЗАПИСЕЙ
            // Чтобы если кто-то записался на 10:15, это время тоже появилось в кнопках
            $existingRecordsTimes = \App\Models\Record::query()
                ->whereDate('datetime', $date)
                ->where('point_id', $pointId)
                ->when($currentId, fn($q) => $q->where('id', '!=', $currentId))
                ->where('status', '!=', 'Отменена')
                ->get()
                ->map(function ($record) {
                    return \Carbon\Carbon::parse($record->datetime)->format('H:i');
                })
                ->toArray();

            // 3. ОБЪЕДИНЯЕМ И СОРТИРУЕМ
            // array_merge - сливаем два массива
            // array_unique - убираем дубли (если запись на 11:00, и в сетке 11:00)
            $allSlots = array_unique(array_merge($baseSlots, $existingRecordsTimes));

            // Сортируем по времени (чтобы 10:15 встало перед 11:00)
            sort($allSlots);

            $options = [];
            foreach ($allSlots as $slot) {
                $result = $checkSlotStatus($date, $pointId, $slot, $currentId);

                if ($result['status'] === 'busy') {
                    $options[$slot] = "$slot ⛔" . $result['info'];
                } elseif ($result['status'] === 'warning') {
                    $options[$slot] = "$slot ⚠️";
                } else {
                    // Если слот нестандартный (например 10:15) и вдруг оказался свободным
                    // (например, запись удалили, но кеш остался, или странный баг),
                    // мы всё равно его покажем. Но в данном алгоритме он сюда попадет
                    // только если занят.

                    // Маленький нюанс: мы хотим видеть в "белых" кнопках только стандартную сетку?
                    // Если да, то можно добавить проверку: in_array($slot, $baseSlots).
                    // Но лучше показывать всё доступное.
                    $options[$slot] = $slot;
                }
            }
            return $options;
        };

        return $form
            ->schema([
                Hidden::make('id'),

                Forms\Components\Section::make('Выбор времени и места')
                    ->schema([
                        Forms\Components\Group::make()
                            ->columns(2)
                            ->schema([
                                Forms\Components\Select::make('point_id')
                                    ->label('Точка')
                                    ->relationship('point', 'name')
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set) => $set('time_only', null))
                                    ->afterStateUpdated(fn(Set $set) => $set('room_id', null))
                                    ->required(),

                                DatePicker::make('date_only')
                                    ->label('Дата игры')
                                    ->displayFormat('d.m.Y')
                                    ->default(now())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set) => $set('time_only', null)),
                            ]),

                        Group::make()->schema([

                            // 1. БЫСТРЫЕ КНОПКИ (Твоя сетка)
                            ToggleButtons::make('time_only')
                                ->label('Быстрый выбор (Стандарт)')
                                ->options($getGridOptions)
                                ->disableOptionWhen(fn($value, $label) => str_contains($label, '⛔'))
                                ->colors([
                                    'danger' => fn($state, $label) => str_contains($label, '⛔'),
                                    'warning' => fn($state, $label) => str_contains($label, '⚠️'),
                                    'primary' => fn($state, $label) => !str_contains($label, '⛔') && !str_contains($label, '⚠️'),
                                ])
                                ->inline()
                                ->live(), // Важно: обновляет состояние для ручного ввода

                            // 2. РУЧНОЙ ВВОД (Для нестандартного времени)
                            TimePicker::make('time_only')
                                ->label('Точное время (Ручной ввод)')
                                ->seconds(false) // Убираем секунды
                                ->minutesStep(5) // Шаг 5 минут
                                ->live()         // Слушаем изменения
                                ->suffixIcon('heroicon-m-clock')
                                ->required(),

                            // 3. ПРЕДУПРЕЖДЕНИЕ (Появляется при опасном выборе)
                            Placeholder::make('warning_alert')
                                ->hiddenLabel()
                                ->content(function (Get $get) use ($checkSlotStatus) {
                                    $date = $get('date_only');
                                    $point = $get('point_id');
                                    $time = $get('time_only'); // Берем то, что ввели (кнопкой или руками)
                                    $id = $get('id');

                                    $check = $checkSlotStatus($date, $point, $time, $id);

                                    if ($check['status'] === 'busy') {
                                        return new \Illuminate\Support\HtmlString("
                                        <div class='text-danger-600 font-bold flex items-center gap-2 p-2 bg-danger-50 rounded border border-danger-200'>
                                            ⛔ ВНИМАНИЕ: Это время пересекается с другой игрой!
                                        </div>
                                    ");
                                    }
                                    if ($check['status'] === 'warning') {
                                        return new \Illuminate\Support\HtmlString("
                                        <div class='text-warning-600 font-bold flex items-center gap-2 p-2 bg-yellow-50 rounded border border-yellow-200'>
                                            ⚠️ ПРЕДУПРЕЖДЕНИЕ: Маленький перерыв между играми (< 90 мин). Возможны накладки!
                                        </div>
                                    ");
                                    }
                                    return null;
                                }),
                        ]),
                    ]),

                // --- КЛИЕНТ ---
                Forms\Components\Section::make('Клиент')
                    ->columns(2)
                    ->schema([
                        // 1. ПОЛЕ ТЕЛЕФОНА (Виртуальное, ищет клиента)
                        PhoneInput::make('client_phone')
                            ->label('Телефон')
                            ->required()
                            ->placeholder('8 777 123 1234') // Подскажем формат
                            ->live(onBlur: true)
                            ->defaultCountry("KZ")
//                            ->showFlags(false)
                            ->disallowDropdown()
                            // PHP часть остается стандартной (очистка от мусора перед поиском)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) return;

                                $digits = preg_replace('/\D/', '', $state);

                                // Коррекция 8 -> 7 (на случай, если JS не сработал)
                                if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
                                    $digits = '7' . substr($digits, 1);
                                } elseif (strlen($digits) === 10) {
                                    $digits = '7' . $digits;
                                }

                                $searchPhone = '+' . $digits;

                                $guest = \App\Models\Guest::where('phone', $searchPhone)->first();

                                if ($guest) {
                                    $set('client_name', $guest->name);
                                    $set('guest_id', $guest->id);
                                    $set('client_phone', $guest->phone);
                                    $set('client_birthday', $guest->birthday);
//                                    dd($guest->birthday);
                                    // Уведомление...
                                } else {
                                    $set('guest_id', null);
                                }
                            })
                            ->afterStateHydrated(function (PhoneInput $component, $state, $record) {
                                if ($record && $record->guest) {
                                    $component->state($record->guest->phone);
                                }
                            }),

                        // 2. ПОЛЕ ИМЕНИ (Виртуальное)
                        Forms\Components\TextInput::make('client_name')
                            ->label('Имя')
                            ->required()
                            // При открытии редактирования - заполняем имя из связи
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                if ($record && $record->guest) {
                                    $component->state($record->guest->name);
                                }
                            }),
                        Forms\Components\DatePicker::make('client_birthday')
                            ->label('Дата рождения')
//                            ->required()
                            // При открытии редактирования - заполняем имя из связи
                            ->afterStateHydrated(function (Forms\Components\DatePicker $component, $state, $record) {
                                Log::info("Hydrating birthday for record ID: " . ($record ? $record->id : 'null'));
                                if ($record && $record->guest) {
                                    $component->state($record->guest->birthday);
                                }
                            }),

                        // 3. СКРЫТОЕ ПОЛЕ ID (Магия сохранения)
                        Forms\Components\Hidden::make('guest_id'),
                        //                            ->required()
                        //                            // dehydrateStateUsing срабатывает ПЕРЕД сохранением в БД
                        //                            ->dehydrateStateUsing(function ($state, Forms\Get $get) {
                        //                                // Если ID уже есть (нашли клиента) - просто возвращаем его
                        //                                if ($state) return $state;
                        //
                        //                                // Если ID нет - значит это НОВЫЙ клиент. Создаем его прямо сейчас.
                        //                                $phone = $get('client_phone');
                        //                                $name = $get('client_name');
                        //
                        //                                if ($phone && $name) {
                        //                                    // firstOrCreate для защиты от дублей, если оператор ввел существующий номер, но скрипт не успел отработать
                        //                                    $guest = \App\Models\Guest::firstOrCreate(
                        //                                        ['phone' => $phone],
                        //                                        ['name' => $name]
                        //                                    );
                        //                                    return $guest->id;
                        //                                }
                        //
                        //                                return null;
                        //                            }),
                    ]),

                // --- ЗАЛ ---
                Forms\Components\Section::make('Аренда зала')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('room_id')
                            ->label('Банкетный зал')
                            ->relationship('room', 'id', function (Builder $query, Get $get) {
                                $pointId = $get('point_id');
                                if ($pointId) $query->where('point_id', $pointId);
                            })
                            ->required(fn(Get $get) => (int) $get('estimated_room_time') > 0)
                            ->validationMessages([
                                'required' => 'Для аренды нужно выбрать зал.',
                            ])
                            ->getOptionLabelFromRecordUsing(fn($record) => "Зал {$record->name} — {$record->price_per_hour} ₸/ч")
                            ->live()
                            ->afterStateUpdated($calculateTotals),


                        Forms\Components\TextInput::make('estimated_room_time')
                            ->label('Часов аренды')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->suffix('ч.')
                            ->live(onBlur: true)
                            ->afterStateUpdated($calculateTotals)
                            ->rule(function (Get $get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    // Если часов больше 0, А зал НЕ выбран -> Ошибка
                                    if ($value > 0 && empty($get('room_id'))) {
                                        $fail('Нельзя указать время аренды без выбора зала.');
                                    }
                                    if($value < 0){
                                        $fail('Время аренды не может быть отрицательным.');
                                    }
                                };
                            }),
                    ]),

                // --- ИГРЫ ---
                Forms\Components\Section::make('Игры и Услуги')
                    ->schema([
                        Forms\Components\Repeater::make('recordActivities')
                            ->relationship()
                            ->label('Список услуг')
                            ->defaultItems(0)
                            ->live()
                            ->afterStateUpdated($calculateTotals)
                            ->schema([
                                Forms\Components\Select::make('activity_id')
                                    ->label('Игра')
                                    ->relationship('activity', 'name')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) use ($calculateTotals) {
                                        $price = Activity::find($state)?->base_price;
                                        $set('fixed_price', $price);
                                        $set("players_count", 1);
                                        //$calculateTotals($get, $set);
                                    })
                                    ->columnSpan(4),
                                Forms\Components\Select::make('difficulty_level')
                                    ->label('Уровень страха')
                                    ->options([
                                        'light' => 'Light (Лайт)',
                                        'medium' => 'Medium (Медиум)',
                                        'hard' => 'Hard (Хард)',
                                        'ultra_hard' => 'Ultra Hard (Ультра)',
                                    ])
                                    ->columnSpan(3) // Впишем рядом с ценой
                                    // ЛОГИКА ОТОБРАЖЕНИЯ
                                    ->visible(function (Forms\Get $get) {
                                        $activityId = $get('activity_id');
                                        if (!$activityId) return false;
                                        return $activityId==1||$activityId==7;}) 
                                    ->required(fn(Forms\Get $get) => /* То же условие, что и в visible */ in_array($get('activity_id'), [1,7])),

                                Forms\Components\TextInput::make('players_count')
                                    ->label('Количество')
                                    ->numeric()
                                    ->columnSpan(2)
                                    ->live(onBlur: true)
                                    ->default(1)
                                    ->afterStateUpdated($calculateTotals),

                                Forms\Components\TextInput::make('fixed_price')
                                    ->label('Цена')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(3)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated($calculateTotals),

                                Forms\Components\TextInput::make('discount_multiplier')
                                    ->label('Скидка')
                                    ->numeric()
                                    ->default(1.0)
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->columnSpan(3)
                                    ->formatStateUsing(function ($state) {
                                        if (is_null($state) || $state == 1.0) return 0;
                                        return round((1 - $state) * 100);
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        if (empty($state)) return 1.0;
                                        return 1 - ($state / 100);
                                    })
                                    ->live(onBlur: true)
                                    ->afterStateUpdated($calculateTotals),
                                Forms\Components\Repeater::make('employeeRecordActivities')
                                    ->relationship('employeeRecordActivities')
                                    ->label('Сотрудники на эту игру')
                                    // 1. Растягиваем блок на всю ширину (убираем "сплющивание")
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    // 2. Делаем сетку: 3 карточки сотрудников в ряд
                                    ->grid(3)
                                    ->schema([
                                        // Внутри одной карточки поля лучше оставить вертикально или compact
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Forms\Components\Select::make('employee_id')
                                                    ->label('Сотрудник')
                                                    ->options(\App\Models\Employee::all()->pluck('name', 'id'))
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                        if (!$state) return;

                                                        // ХАК: $get('../../activity_id') берет ID из родительского репитера
                                                        $activityId = $get('../../activity_id');

                                                        if ($activityId) {
                                                            $activity = \App\Models\Activity::find($activityId);
                                                            $baseRate = $activity->employee_rate ?? 0;

                                                            $rateMultiplier = \App\Models\EmployeeActivityRate::where('employee_id', $state)
                                                                ->where('activity_id', $activityId)
                                                                ->value('rate_multiplier') ?? 1.0;

                                                            $set('wage', $baseRate * $rateMultiplier);
                                                        }
                                                    }),

                                                Forms\Components\Hidden::make('wage')
                                                    ->default(0)
                                                    ->dehydrated(),

                                                Forms\Components\Select::make('role')
                                                    ->label('Роль')
                                                    ->options([
                                                        1 => 'Админ',
                                                        2 => 'Оператор',
                                                        3 => 'Актер',
                                                        4 => 'Помощник',
                                                        5 => 'Стажер'
                                                    ])
                                                    ->default(1)
                                                    ->selectablePlaceholder(false),
                                            ])
                                            // Немного визуального оформления внутри карточки
                                            ->columns(1),
                                    ])
                                    ->addActionLabel('Добавить сотрудника'),
                            ])
                            ->columns(12)
                            ->addActionLabel('Добавить услугу'),
                    ]),

                // --- ФИНАНСЫ ---
                Forms\Components\Section::make('Расчет')
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('total')
                            ->label('ИТОГО')
                            ->prefix('₸')
                            ->numeric()
                            ->default(0)
                            ->live(),

                        Forms\Components\TextInput::make('prepaid')
                            ->label('Предоплата')
                            ->prefix('₸')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true),

                        Forms\Components\Placeholder::make('remaining')
                            ->label('К доплате')
                            ->content(function (Get $get) {
                                $total = (float) $get('total');
                                $prepaid = (float) $get('prepaid');
                                $diff = $total - $prepaid;
                                $color = $diff <= 0 ? 'green' : 'red';
                                return new HtmlString("<span style='font-size: 1.25rem; font-weight: bold; color: {$color};'>" . number_format($diff, 0, '.', ' ') . " ₸</span>");
                            }),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'Подтверждена'=> 'Подтверждена',
                                'Оплачено наличными'=> 'Оплачено наличными',
                                'Оплачено безналичными'=> 'Оплачено безналичными',
                                'Оплачено смешанно'=> 'Оплачено смешанно',
                                'Отменена'=> 'Отменена',
                            ])
                            ->default('Подтверждена')
                            ->required(),
                    ]),
                Forms\Components\Section::make('Дополнительно')
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('note')
                            ->label('Заметка (для списка)')
                            ->placeholder('Например: 2 команды, делим по очереди')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('comment')
                            ->label('Комментарий администратора')
                            ->placeholder('Особенности клиентов, пожелания и т.д.')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('datetime')->dateTime('d.m.Y H:i')->label('Время')->sortable(),
                Tables\Columns\TextColumn::make('guest.phone')->label('Телефон')->searchable(),
                Tables\Columns\TextColumn::make('point.name')->label('Точка')->sortable(),
                Tables\Columns\TextColumn::make('room.area')->label('Зал')->suffix(' м²'),
                Tables\Columns\TextColumn::make('total')->money('kzt')->label('Сумма')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Подтверждена' => 'info',
                        'Оплачено','Оплачено наличными', 'Оплачено безналичными' => 'success','Оплачено смешанно' => 'success',
                        'Отменена' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([])
            ->actions([
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecords::route('/'),
            'create' => Pages\CreateRecord::route('/create'),
            'edit' => Pages\EditRecord::route('/{record}/edit'),
        ];
    }
}
