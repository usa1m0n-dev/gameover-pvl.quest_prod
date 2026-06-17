<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Group;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;
    protected function getHeaderActions(): array
    {
        return [
            Action::make('fast_booking')
                ->label('Быстрая запись')
                ->icon('heroicon-s-bolt') // Иконка молнии (залитая)
                ->color('warning') // Желтый/Оранжевый цвет, чтобы привлекало внимание
                ->size('xl') // Большой размер
                ->url(FastBooking::getUrl()) // Ссылка на твою новую страницу
                ->extraAttributes([
                    'class' => 'shadow-lg hover:shadow-xl transition-all', // Немного тени для объема
                ]),
        ];
    }
    public static function shouldRegisterNavigation(): bool
    {
        // Показывать только если есть роль supermanager
        return auth()->user()->hasRole('supermanager');
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Фильтры статистики')
                    ->columns(4)
                    ->hidden(!auth()->user()->hasRole('supermanager'))
                    ->schema([
                        // 1. ВЫБОР ТОЧКИ
                        Select::make('point_id')
                            ->label('Точка')
                            ->options(\App\Models\Point::all()->pluck('name', 'id'))
                            ->placeholder('Все точки')
                            ->columnSpan(1),

                        // 2. ПРЕСЕТЫ (БЫСТРЫЕ КНОПКИ)
                        Select::make('period_preset')
                            ->label('Период')
                            ->placeholder('Произвольный')
                            ->options([
                                'today' => 'Сегодня',
                                'yesterday' => 'Вчера',
                                'this_week' => 'Эта неделя',
                                'prev_week' => 'Прошлая неделя',
                                'this_month_progress' => 'Этот месяц (с начала)',
                                'this_month_full' => 'Весь этот месяц',
                                'prev_month' => 'Прошлый месяц',
                                'this_year' => 'Этот год',
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state) return;

                                $start = null;
                                $end = null;

                                switch ($state) {
                                    case 'today':
                                        $start = now()->startOfDay();
                                        $end = now()->endOfDay();
                                        break;
                                    case 'yesterday':
                                        $start = now()->subDay()->startOfDay();
                                        $end = now()->subDay()->endOfDay();
                                        break;
                                    case 'this_week':
                                        $start = now()->startOfWeek();
                                        $end = now()->endOfWeek();
                                        break;
                                    case 'prev_week':
                                        $start = now()->subWeek()->startOfWeek();
                                        $end = now()->subWeek()->endOfWeek();
                                        break;
                                    case 'this_month_progress':
                                        // "С начала месяца по сейчас" (чтобы видеть текущий результат)
                                        $start = now()->startOfMonth();
                                        $end = now()->endOfDay();
                                        break;
                                    case 'this_month_full':
                                        // "Весь месяц" (включая будущие брони до конца месяца)
                                        $start = now()->startOfMonth();
                                        $end = now()->endOfMonth();
                                        break;
                                    case 'prev_month':
                                        $start = now()->subMonth()->startOfMonth();
                                        $end = now()->subMonth()->endOfMonth();
                                        break;
                                    case 'this_year':
                                        $start = now()->startOfYear();
                                        $end = now()->endOfYear();
                                        break;
                                }

                                if ($start && $end) {
                                    $set('startDate', $start->format('Y-m-d'));
                                    $set('endDate', $end->format('Y-m-d'));
                                }
                            })
                            ->columnSpan(1),

                        // 3. КАЛЕНДАРИ (Обновляются автоматически или вручную)
                        Group::make()
                            ->schema([
                                DatePicker::make('startDate')
                                    ->label('С даты')
                                    ->required()
                                    ->default(now()->startOfMonth())
                                    // Если меняем руками, сбрасываем пресет
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('period_preset', null)),

                                DatePicker::make('endDate')
                                    ->label('По дату')
                                    ->required()
                                    ->default(now()->endOfMonth())
                                    // Если меняем руками, сбрасываем пресет
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('period_preset', null)),
                            ])
                            ->columnSpan(2)
                            ->columns(2),
                    ]),
            ]);
    }
    public function getColumns(): int | string | array
    {
        return 2; // Делим экран на 2 колонки
    }
}
