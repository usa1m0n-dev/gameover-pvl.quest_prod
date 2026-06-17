<?php

namespace App\Filament\Widgets;

use App\Models\RecordActivity;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class PopularGamesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $maxHeight = '250px';

    // 2. Ширина: Занимает 1 колонку из сетки (а не 'full')
    protected int | string | array $columnSpan = 1;

    // Опционально: Сортировка, чтобы он встал, например, вторым или третьим
    protected static ?int $sort = 3;
    public static function canView(): bool
    {
        return auth()->user()->hasRole('supermanager');
    }

    protected function getData(): array
    {
        $startDate = !empty($this->filters['startDate']) ? Carbon::parse($this->filters['startDate'])->startOfDay() : now()->startOfMonth();
        $endDate = !empty($this->filters['endDate']) ? Carbon::parse($this->filters['endDate'])->endOfDay() : now()->endOfMonth();

        $pointId = $this->filters['point_id'] ?? null;

        // Группируем по названию активности и считаем количество
        $data = RecordActivity::query()
            ->join('activities', 'record_activities.activity_id', '=', 'activities.id')
            ->join('records', 'record_activities.record_id', '=', 'records.id')
            ->whereBetween('records.datetime', [$startDate, $endDate])
            ->when($pointId, fn($q) => $q->where('records.point_id', $pointId))
            ->whereNot('records.status', "Отменена")
            ->select('activities.name', DB::raw('count(*) as total'))
            ->groupBy('activities.name')
            ->orderByDesc('total')
            ->limit(5) // Топ 5
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Количество игр',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => ['#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899'], // Разные цвета
                ],
            ],
            'labels' => $data->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut'; // Бублик смотрится лучше для долей
    }
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right', // Легенда справа, а не сверху/снизу
                ],
            ],
        ];
    }
}
