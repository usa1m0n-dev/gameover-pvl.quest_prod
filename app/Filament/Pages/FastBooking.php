<?php
namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\Guest;
use App\Models\PartyRoom;
use App\Models\Point;
use App\Models\Record;
use App\Models\RecordActivity;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class FastBooking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static string $view = 'filament.pages.fast-booking';
    protected static ?string $title = 'Быстрая запись';
    protected static ?string $navigationLabel = 'Быстрая запись';
    protected static ?string $navigationGroup = 'Сервисное';
    protected static ?int $navigationSort = 0;

    public ?array $data = [];

    public function mount(): void
    {
        // Достаем базовую цену квеста по умолчанию (id: 1), чтобы предпросчитать итог
        $defaultActivity = Activity::find(1);
        $defaultPrice = $defaultActivity ? $defaultActivity->base_price : 0;
        $defaultTotal = $defaultPrice * 4; // 4 игрока по умолчанию

        // Изначально предзаполняем форму
        $this->form->fill([
            'date_only' => now()->format('Y-m-d'),
            'activities' => [
                ['activity_id' => 1, 'players_count' => 4]
            ],
            'prepaid' => 1000,
            'estimated_room_time' => 0,
            'total' => $defaultTotal, // <-- Сразу записываем готовую сумму
        ]);
    }

    public function form(Form $form): Form
    {
        // --- ЛОГИКА СЛОТОВ ---
        $checkSlotStatus = function ($date, $pointId, $timeToCheck) {
            if (!$date || !$pointId || !$timeToCheck) return ['status' => 'ok', 'info' => ''];

            $slotTime = Carbon::parse($date . ' ' . $timeToCheck);
            $busyRecords = Record::query()
                ->whereDate('datetime', $date)
                ->where('point_id', $pointId)
                ->where('status', '!=', 'Отменена')
                ->get();

            $finalStatus = 'ok';
            $finalInfo = '';

            foreach ($busyRecords as $record) {
                $gameStart = Carbon::parse($record->datetime);
                $gameEnd = $gameStart->copy()->addMinutes(60);

                if ($slotTime->greaterThanOrEqualTo($gameStart) && $slotTime->lessThan($gameEnd)) {
                    $finalStatus = 'busy';
                    if ($slotTime->equalTo($gameStart) && $record->room_id) {
                        $hours = $record->estimated_room_time > 0 ? " {$record->estimated_room_time}ч" : "";
                        $finalInfo = " ({$record->room->name} - {$hours})";
                    }
                    break;
                }

                if ($finalStatus !== 'busy') {
                    if (abs($slotTime->diffInMinutes($gameStart)) < 90) {
                        $finalStatus = 'warning';
                    }
                }
            }
            return ['status' => $finalStatus, 'info' => $finalInfo];
        };

        $getGridOptions = function (Get $get) use ($checkSlotStatus) {
            $date = $get('date_only');
            $pointId = $get('point_id');

            if (!$date || !$pointId) return [];

            $baseSlots = ['11:00', '12:00', '13:30', '14:00', '15:30', '16:00', '17:00', '17:30', '18:30', '19:00', '20:30', '22:00'];

            $existingRecordsTimes = Record::query()
                ->whereDate('datetime', $date)
                ->where('point_id', $pointId)
                ->where('status', '!=', 'Отменена')
                ->get()
                ->map(fn ($record) => Carbon::parse($record->datetime)->format('H:i'))
                ->toArray();

            $allSlots = array_unique(array_merge($baseSlots, $existingRecordsTimes));
            sort($allSlots);

            $options = [];
            foreach ($allSlots as $slot) {
                $result = $checkSlotStatus($date, $pointId, $slot);
                if ($result['status'] === 'busy') {
                    $options[$slot] = "$slot ⛔" . $result['info'];
                } elseif ($result['status'] === 'warning') {
                    $options[$slot] = "$slot ⚠️";
                } else {
                    $options[$slot] = $slot;
                }
            }
            return $options;
        };

        // --- ЛОГИКА КАЛЬКУЛЯТОРА ---
        $calculateTotals = function (Get $get, Set $set) {
            $total = 0;

            // 1. Считаем зал
            $roomId = $get('room_id');
            $hours = (float) $get('estimated_room_time');
            if ($roomId && $hours > 0) {
                $room = PartyRoom::find($roomId);
                if ($room) {
                    $total += ($room->price_per_hour * $hours);
                }
                $set('prepaid', 5000); // Зал есть - предоплата 5000
            } else {
                $set('prepaid', 1000); // Зала нет - предоплата 1000
            }

            // 2. Считаем активности (квесты)
            $activities = $get('activities') ?? [];
            foreach ($activities as $item) {
                if (!empty($item['activity_id'])) {
                    $activity = Activity::find($item['activity_id']);
                    $price = $activity ? $activity->base_price : 0;
                    $players = (int) ($item['players_count'] ?? 4);

                    $total += ($price * $players);
                }
            }

            $set('total', $total);
        };

        return $form
            ->schema([
                Group::make()->schema([
                    // БЛОК 1: ВРЕМЯ И МЕСТО
                    Section::make('Время и место')
                        ->schema([
                            Group::make()->columns(2)->schema([
                                Select::make('point_id')
                                    ->label('Точка')
                                    ->options(Point::pluck('name', 'id'))
                                    ->live()
                                    ->afterStateUpdated(function(Set $set) {
                                        $set('time_only', null);
                                        $set('room_id', null); // Сброс зала при смене точки
                                    })
                                    ->required(),

                                DatePicker::make('date_only')
                                    ->label('Дата игры')
                                    ->displayFormat('d.m.Y')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set) => $set('time_only', null)),
                            ]),

                            ToggleButtons::make('time_only')
                                ->label('Слоты')
                                ->options($getGridOptions)
                                ->disableOptionWhen(fn($value, $label) => str_contains($label, '⛔'))
                                ->colors([
                                    'danger' => fn($state, $label) => str_contains($label, '⛔'),
                                    'warning' => fn($state, $label) => str_contains($label, '⚠️'),
                                    'primary' => fn($state, $label) => !str_contains($label, '⛔') && !str_contains($label, '⚠️'),
                                ])
                                ->inline()
                                ->live()
                                ->required(),

                            TimePicker::make('time_only_manual')
                                ->label('Или укажите вручную')
                                ->seconds(false)
                                ->minutesStep(5)
                                ->live()
                                ->afterStateUpdated(fn(Set $set, $state) => $set('time_only', $state)),
                        ]),

                    // БЛОК 2: ИГРЫ (REPEATER)
                    Section::make('Квесты и услуги')
                        ->schema([
                            Repeater::make('activities')
                                ->hiddenLabel()
                                ->addActionLabel('Добавить игру')
                                ->defaultItems(1)
                                ->default([
                                    ['activity_id' => 7],
                                ])
                                ->schema([
                                    Group::make()->columns(2)->schema([
                                        Select::make('activity_id')
                                            ->label('Игра')
                                            ->options(Activity::pluck('name', 'id'))
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated($calculateTotals),

                                        TextInput::make('players_count')
                                            ->label('Кол-во человек')
                                            ->numeric()
                                            ->default(4)
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated($calculateTotals),
                                    ])
                                ])
                                ->live()
                                ->afterStateUpdated($calculateTotals),
                        ]),
                ])->columnSpan(7),

                Group::make()->schema([
                    // БЛОК 3: КЛИЕНТ
                    Section::make('Данные клиента')
                        ->schema([
                            PhoneInput::make('client_phone')
                                ->label('Телефон')
                                ->required()
                                ->defaultCountry("KZ")
                                ->disallowDropdown()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if (!$state) return;
                                    $digits = preg_replace('/\D/', '', $state);
                                    if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
                                        $digits = '7' . substr($digits, 1);
                                    } elseif (strlen($digits) === 10) {
                                        $digits = '7' . $digits;
                                    }
                                    $guest = Guest::where('phone', '+' . $digits)->first();
                                    if ($guest) {
                                        $set('client_name', $guest->name);
                                    }
                                }),
                            TextInput::make('client_name')
                                ->label('Имя')
                                ->required(),
                        ]),

                    // БЛОК 4: БАНКЕТНЫЙ ЗАЛ И ОПЛАТА
                    Section::make('Банкетный зал и Оплата')
                        ->schema([
                            Group::make()->columns(2)->schema([
                                Select::make('room_id')
                                    ->label('Банкетный зал')
                                    // Фильтрация залов по выбранной точке
                                    ->options(fn (Get $get) => PartyRoom::where('point_id', $get('point_id'))->pluck('name', 'id'))
                                    ->live()
                                    ->afterStateUpdated($calculateTotals),

                                TextInput::make('estimated_room_time')
                                    ->label('Часов аренды')
                                    ->numeric()
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated($calculateTotals),
                            ]),

                            Group::make()->columns(2)->schema([
                                TextInput::make('prepaid')
                                    ->label('Предоплата')
                                    ->numeric()
                                    ->prefix('₸')
                                    ->required(),

                                TextInput::make('total')
                                    ->label('Итоговая сумма')
                                    ->numeric()
                                    ->prefix('₸')
                                    ->readOnly(), // Защищаем от ручного ввода, чтобы не ломать калькулятор
                            ]),
                        ]),
                ])->columnSpan(5),
            ])
            ->columns(12)
            ->statePath('data');
    }

    public function submit()
    {
        $data = $this->form->getState();

        DB::transaction(function () use ($data) {
            // 1. Гость
            $digits = preg_replace('/\D/', '', $data['client_phone']);
            if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
                $digits = '7' . substr($digits, 1);
            } elseif (strlen($digits) === 10) {
                $digits = '7' . $digits;
            }
            $guest = Guest::firstOrCreate(
                ['phone' => '+' . $digits],
                ['name' => $data['client_name']]
            );

            // 2. Создание Record
            $record = Record::create([
                'point_id' => $data['point_id'],
                'guest_id' => $guest->id,
                'datetime' => $data['date_only'] . ' ' . ($data['time_only'] ?? $data['time_only_manual']),
                'room_id' => $data['room_id'] ?? null,
                'estimated_room_time' => $data['estimated_room_time'] ?? 0,
                'total' => $data['total'] ?? 0,
                'prepaid' => $data['prepaid'] ?? 0,
                'status' => 'Подтверждена',
               ]);

            // 3. Добавление всех активностей из Repeater
            if (!empty($data['activities'])) {
                foreach ($data['activities'] as $act) {
                    if (!empty($act['activity_id'])) {
                        $activity = Activity::find($act['activity_id']);
                        RecordActivity::create([
                            'record_id' => $record->id,
                            'activity_id' => $activity->id,
                            'fixed_price' => $activity->base_price ?? 0,
                            'players_count' => $act['players_count'] ?? 4,
                            'discount_multiplier' => 0,
                        ]);
                    }
                }
            }
        });

        Notification::make()
            ->title('Игра успешно забронирована!')
            ->success()
            ->send();

        // Сброс формы (оставляем точку, дату и дефолтную игру)
        $this->form->fill([
            'point_id' => $data['point_id'],
            'date_only' => $data['date_only'],
            'activities' => [['activity_id' => 1, 'players_count' => 4]],
            'prepaid' => 1000,
            'estimated_room_time' => 0,
        ]);
    }
}
