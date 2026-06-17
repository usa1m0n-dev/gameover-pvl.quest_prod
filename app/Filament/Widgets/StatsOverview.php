<?php

namespace App\Filament\Widgets;

use App\Models\Record;
use App\Models\RecordActivity;
use App\Models\EmployeeRecordActivity;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters; // <-- ВАЖНО: Подключаем фильтры с дашборда
    public static function canView(): bool
    {
        return auth()->user()->hasRole('supermanager');
    }
    protected function getStats(): array
    {
        // 1. ПРАВИЛЬНАЯ ОБРАБОТКА ДАТ
        $startDate = !empty($this->filters['startDate'])
            ? Carbon::parse($this->filters['startDate'])->startOfDay()
            : now()->startOfMonth();

        $endDate = !empty($this->filters['endDate'])
            ? Carbon::parse($this->filters['endDate'])->endOfDay() // <--- ВОТ ЭТО ИСПРАВЛЕНИЕ
            : now()->endOfMonth();

        $pointId = $this->filters['point_id'] ?? null;

        // 2. Базовый запрос
        $query = Record::query()
            ->whereBetween('datetime', [$startDate, $endDate])
            ->when($pointId, fn($q) => $q->where('point_id', $pointId));

        // --- РАСЧЕТЫ ---

        // 1. Кол-во оплаченных/подтвержденных (считаем успешными)
        $paidRecordsCount = (clone $query)
            ->whereNot('status', 'Отменена')
            ->count();

        // 2. Отмены
        $cancelledCount = (clone $query)
            ->where('status', 'Отменена')
            ->count();

        // 3. Выручка (только успешные)
        $revenue = (clone $query)
            ->whereNot('status', 'Отменена')
            ->sum('total');
        $revenue = $revenue / 100;

        // 4. Затраты на ЗП (Cost of Labor)
        // Нам нужно найти все активности внутри этих записей
        $recordIds = (clone $query)->pluck('id');
        $wagesCost = EmployeeRecordActivity::whereHas('recordActivity', function ($q) use ($recordIds) {
                $q->whereIn('record_id', $recordIds);
            })->sum('wage') / 100; // Тоже делим на 100

        // Чистая прибыль (Выручка - ЗП)
        $netProfit = $revenue - $wagesCost;

        // 5. Статистика по комнатам
        $roomRecords = (clone $query)->whereNotNull('room_id')->where('estimated_room_time', '>', 0);
        $roomCount = $roomRecords->count();
        $avgRoomDuration = $roomCount > 0
            ? round($roomRecords->avg('estimated_room_time'), 1)
            : 0;

        // 6. Среднее кол-во игроков
        // Идем через таблицу связку
        $avgPlayers = RecordActivity::whereIn('record_id', $recordIds)->avg('players_count');
        $avgPlayers = round($avgPlayers ?? 0, 1);

        return [
            Stat::make('Выручка', number_format($revenue, 0, '.', ' ') . ' ₸')
                ->description('ЗП сотрудников: ' . number_format($wagesCost, 0, '.', ' ') . ' ₸')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([$revenue * 0.8, $revenue * 0.9, $revenue]), // Просто для красоты

            Stat::make('Чистая прибыль (Point)', number_format($netProfit, 0, '.', ' ') . ' ₸')
                ->description('Выручка минус ЗП за игры')
                ->color($netProfit > 0 ? 'success' : 'danger'),

            Stat::make('Всего игр', $paidRecordsCount)
                ->description("Отмен: {$cancelledCount}")
                ->color('primary'),

            Stat::make('Аренда комнат', $roomCount . ' раз')
                ->description("Среднее время: {$avgRoomDuration} ч.")
                ->color('info'),

            Stat::make('Среднее кол-во игроков', $avgPlayers)
                ->icon('heroicon-o-users'),
        ];
    }
}
