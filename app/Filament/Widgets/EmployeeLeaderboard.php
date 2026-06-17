<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class EmployeeLeaderboard extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Топ сотрудников (по играм)';
    protected int | string | array $columnSpan = 'full'; // На всю ширину
    public static function canView(): bool
    {
        return auth()->user()->hasRole('supermanager');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->withCount(['shifts' => function (Builder $query) {

                        // Фильтруем смены по дате записи
                        $startDate = !empty($this->filters['startDate']) ? Carbon::parse($this->filters['startDate'])->startOfDay() : now()->startOfMonth();
                        $endDate = !empty($this->filters['endDate']) ? Carbon::parse($this->filters['endDate'])->endOfDay() : now()->endOfMonth();

                        $pointId = $this->filters['point_id'] ?? null;

                        $query->whereHas('recordActivity.record', function ($q) use ($startDate, $endDate, $pointId) {
                            $q->whereBetween('datetime', [$startDate, $endDate]) // Теперь ищет до 23:59:59
                            ->when($pointId, fn($sq) => $sq->where('point_id', $pointId));
                        });
                    }])
                    ->orderByDesc('shifts_count') // Сортируем: кто больше работал
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Сотрудник')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('position')
                    ->label('Должность'),

                Tables\Columns\TextColumn::make('shifts_count')
                    ->label('Провел игр')
                    ->badge()
                    ->color('success'),

                // Можно добавить и сумму заработанного за период
                Tables\Columns\TextColumn::make('earned_period')
                    ->label('Заработал (период)')
                    ->money('kzt')
                    ->getStateUsing(function (Employee $record) {
                        // Повторяем логику фильтра для суммы
                        $startDate = !empty($this->filters['startDate']) ? Carbon::parse($this->filters['startDate'])->startOfDay() : now()->startOfMonth();
                        $endDate = !empty($this->filters['endDate']) ? Carbon::parse($this->filters['endDate'])->endOfDay() : now()->endOfMonth();
                        $pointId = $this->filters['point_id'] ?? null;

                        $sum = $record->shifts()
                            ->whereHas('recordActivity.record', function ($q) use ($startDate, $endDate, $pointId) {
                                $q->whereBetween('datetime', [$startDate, $endDate])
                                    ->when($pointId, fn($sq) => $sq->where('point_id', $pointId));
                            })
                            ->sum('wage');

                        return $sum / 100;
                    }),
            ]);
    }
}
