<?php
namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\Employee;
use App\Models\RecordActivity;
use App\Models\EmployeeRecordActivity;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class FillShift extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static string $view = 'filament.pages.fill-shift';
    protected static ?string $title = 'Записать смену';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public $point_id;
    public $date;

    public function mount()
    {
        $this->point_id = request()->query('point_id');
        $this->date = request()->query('date', now()->format('Y-m-d'));

        $this->form->fill([
            'date' => $this->date,
            'point_id' => $this->point_id,
        ]);
    }

    public function form(Form $form): Form
    {
        $activeActivityIds = RecordActivity::whereHas('record', function ($query) {
            if ($this->point_id) {
                $query->where('point_id', $this->point_id);
            }
            $query->whereDate('datetime', $this->date);
        })->distinct()->pluck('activity_id');

        $activities = Activity::whereIn('id', $activeActivityIds)->get();

        $sections = [];

        if ($activities->isEmpty()) {
            $sections[] = Section::make('Внимание')
                ->description('На выбранную дату и точку не найдено активных записей.')
                ->schema([
                    Placeholder::make('empty_hint')
                        ->label('Активностей нет')
                        ->content('Пожалуйста, вернитесь в календарь и выберите день с играми.')
                ]);
        }

        foreach ($activities as $activity) {
            $sections[] = Section::make("Квест: {$activity->name}")
                ->collapsible()
                ->schema([
                    // Используем Repeater для выбора сотрудника и роли одновременно
                    Repeater::make("activity_{$activity->id}_employees")
                        ->label('Сотрудники на этом квесте сегодня')
                        ->defaultItems(0)
                        ->grid(3) // Как в твоем основном ресурсе
                        ->schema([
                            Group::make()->schema([
                                Select::make('employee_id')
                                    ->label('Сотрудник')
                                    ->options(Employee::all()->pluck('name', 'id'))
                                    ->required()
                                    ->live()
                                    // Логика расчета ставки
                                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) use ($activity) {
                                        if (!$state) return;

                                        $baseRate = $activity->employee_rate ?? 0;
                                        $rateMultiplier = \App\Models\EmployeeActivityRate::where('employee_id', $state)
                                            ->where('activity_id', $activity->id)
                                            ->value('rate_multiplier') ?? 1.0;

                                        $set('wage', $baseRate * $rateMultiplier);
                                    }),

                                Hidden::make('wage')->default(0),

                                Select::make('role')
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
                            ])->columns(1),
                        ])
                        ->addActionLabel('Добавить сотрудника')
                        ->helperText('Выбранные сотрудники и их роли будут добавлены во ВСЕ слоты этого квеста за текущий день.'),
                ]);
        }

        return $form
            ->schema([
                Section::make('Параметры смены')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('date')
                            ->label('Дата')
                            ->disabled(),
                        Select::make('point_id')
                            ->label('Точка (локация)')
                            ->options(\App\Models\Point::pluck('name', 'id'))
                            ->disabled(),
                    ]),
                ...$sections,
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $formData = $this->form->getState();

        DB::transaction(function () use ($formData) {
            foreach ($formData as $key => $employeesData) {
                // Ищем ключи, начинающиеся на 'activity_' (наши репитеры)
                if (str_starts_with($key, 'activity_')) {
                    $activityId = str_replace(['activity_', '_employees'], '', $key);

                    // Находим все записи (RecordActivity) этого квеста за этот день
                    $recordActivities = RecordActivity::where('activity_id', $activityId)
                        ->whereHas('record', function ($q) {
                            $q->where('point_id', $this->point_id)
                                ->whereDate('datetime', $this->date);
                        })->get();

                    foreach ($recordActivities as $ra) {
                        // 1. Очищаем старые привязки сотрудников к этой игре
                        $ra->employeeRecordActivities()->delete();

                        // 2. Если в репитере ничего не заполнено, пропускаем
                        if (empty($employeesData)) continue;

                        // 3. Создаем новые привязки для каждого сотрудника из репитера
                        foreach ($employeesData as $empData) {
                            EmployeeRecordActivity::create([
                                'record_activity_id' => $ra->id,
                                'employee_id' => $empData['employee_id'],
                                'role' => $empData['role'],
                                'wage' => $empData['wage'] ?? 0,
                            ]);
                        }
                    }
                }
            }
        });

        Notification::make()
            ->title('Смена успешно записана!')
            ->body('Сотрудники и их роли применены ко всем играм.')
            ->success()
            ->send();

        return redirect()->to('/admin/records');
    }
}