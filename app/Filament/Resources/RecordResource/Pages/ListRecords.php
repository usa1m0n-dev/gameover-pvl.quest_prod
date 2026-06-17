<?php

namespace App\Filament\Resources\RecordResource\Pages;

use App\Filament\Resources\RecordResource;
use App\Filament\Widgets\RecordTimeline;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords as BaseListRecords;
use Filament\Resources\Components\Tab; // 1. Импорт для вкладок
use Illuminate\Database\Eloquent\Builder;

class ListRecords extends BaseListRecords
{
    protected static string $resource = RecordResource::class;

    // Используем наш кастомный шаблон
    protected static string $view = 'filament.pages.records-list';

    // Режим по умолчанию
    public $viewMode = 'timeline';

    protected function getHeaderActions(): array
    {
        return [
            // Кнопка переключения
            Actions\Action::make('toggleView')
                ->label(fn () => $this->viewMode === 'timeline' ? 'Показать таблицу' : 'Показать календарь')
                ->icon(fn () => $this->viewMode === 'timeline' ? 'heroicon-o-table-cells' : 'heroicon-o-calendar')
                ->action(function () {
                    $this->viewMode = $this->viewMode === 'timeline' ? 'table' : 'timeline';
                }),

            Actions\CreateAction::make()->label('Создать запись'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        // Виджет календаря показываем ТОЛЬКО в режиме timeline
        if ($this->viewMode === 'timeline') {
            return [
                RecordTimeline::class,
            ];
        }
        return [];
    }

    // 2. МЕТОД ДЛЯ СОЗДАНИЯ "ПАПОК" (ВКЛАДОК)
    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Активные')
                ->icon('heroicon-m-check-circle')
                // Исключаем отмененные (стандартный вид)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', '!=', 'Отменена')),

            'cancelled' => Tab::make('Отмененные')
                ->icon('heroicon-m-trash')
                // Показываем ТОЛЬКО отмененные (Архив/Корзина)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Отменена'))
                ->badgeColor('danger'),

            'all' => Tab::make('Все записи')
                ->icon('heroicon-m-list-bullet'), // Без фильтров
        ];
    }
}
