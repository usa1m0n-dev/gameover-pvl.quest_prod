<?php

namespace App\Filament\Widgets;

use App\Models\Record;
use App\Models\Point; // <-- Импорт
use App\Services\SlotSuggester; // <-- Импорт
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Livewire\Attributes\Url;

class RecordTimeline extends Widget
{
    protected static string $view = 'filament.widgets.record-timeline';
    protected int | string | array $columnSpan = 'full';

    #[Url]
    public ?string $date = null;

    #[Url]
    public ?int $pointId = null; // Сохраняем выбор точки в URL

    public $currentMonth;
    public function setPointId($id) { $this->pointId = $id; }

    public function mount(): void
    {
        if (!$this->date) {
            $this->date = now()->format('Y-m-d');
        }
        $this->currentMonth = Carbon::parse($this->date)->startOfMonth()->format('Y-m-d');

        // Если точка не выбрана, берем первую попавшуюся
        if (!$this->pointId) {
            $this->pointId = Point::first()?->id;
        }
    }

    public function nextMonth(): void
    {
        $this->currentMonth = Carbon::parse($this->currentMonth)->addMonth()->format('Y-m-d');
    }

    public function prevMonth(): void
    {
        $this->currentMonth = Carbon::parse($this->currentMonth)->subMonth()->format('Y-m-d');
    }

    public function goToToday(): void
    {
        $this->date = now()->format('Y-m-d');
        $this->currentMonth = now()->startOfMonth()->format('Y-m-d');
    }

    public function selectDate($date): void
    {
        $this->date = $date;
    }

    // Получаем дни для календаря (с точками занятости)
    public function getCalendarDays(): array
    {
        $monthStart = Carbon::parse($this->currentMonth)->startOfMonth();
        $monthEnd = Carbon::parse($this->currentMonth)->endOfMonth();
        $start = $monthStart->copy()->startOfWeek();
        $end = $monthEnd->copy()->endOfWeek();

        // Получаем занятые даты ТОЛЬКО для выбранной точки
        $busyDates = Record::query()
            ->whereBetween('datetime', [$start, $end])
            ->where('status', '!=', 'Отменена')
            ->when($this->pointId, fn($q) => $q->where('point_id', $this->pointId))
            ->pluck('datetime')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->unique()
            ->toArray();

        $days = [];
        $curr = $start->copy();
        while ($curr->lte($end)) {
            $days[] = [
                'date' => $curr->format('Y-m-d'),
                'day' => $curr->day,
                'isCurrentMonth' => $curr->isSameMonth($monthStart),
                'isToday' => $curr->isToday(),
                'isSelected' => $curr->format('Y-m-d') === $this->date,
                'hasEvents' => in_array($curr->format('Y-m-d'), $busyDates),
            ];
            $curr->addDay();
        }
        return $days;
    }

    // Получаем рекомендации от AI
    public function getSuggestions(): array
    {
        if (!$this->pointId || !$this->date) return [];
        return SlotSuggester::suggest($this->date, $this->pointId);
    }

    // Получаем список записей
    public function getRecords()
    {
        return Record::query()
            ->whereDate('datetime', $this->date)
            ->where('status', '!=', 'Отменена')
            ->when($this->pointId, fn($q) => $q->where('point_id', $this->pointId))
            ->orderBy('datetime')
            ->with(['guest', 'point', 'room'])
            ->get();
    }
}
