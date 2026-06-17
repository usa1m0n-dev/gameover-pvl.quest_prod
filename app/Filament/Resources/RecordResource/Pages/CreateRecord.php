<?php

namespace App\Filament\Resources\RecordResource\Pages;

use App\Filament\Resources\RecordResource;
use App\Models\Guest;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord as BaseCreateRecord;

class CreateRecord extends BaseCreateRecord
{
    protected static string $resource = RecordResource::class;

    // 1. ПРИНИМАЕМ ДАННЫЕ ИЗ URL (для клика по календарю)
    public function mount(): void
    {
        parent::mount();

        $data = [];
        if (request()->has('date')) {
            $data['date_only'] = request()->get('date');
        }
        if (request()->has('time')) {
            $data['time_only'] = request()->get('time');
        }
        if (request()->has('point_id')) {
            $data['point_id'] = request()->get('point_id');
        }

        if (!empty($data)) {
            $this->form->fill($data);
        }
    }

    // 2. ГЛАВНОЕ: ПОДГОТОВКА ДАННЫХ ПЕРЕД СОХРАНЕНИЕМ В БД
    protected function mutateFormDataBeforeCreate(array $data): array
    {
//        if (empty($data['guest_id']) && !empty($data['client_phone'])) {
            // Сначала ищем или создаем (но не обновляем пока)
            $guest = Guest::firstOrCreate(
                ['phone' => $data['client_phone']],
                ['name' => $data['client_name'] ?? 'Гость', 'birthday' => $data['client_birthday']]
            );

            // Подготавливаем массив для обновления
            $updateData = [];

            // Обновляем имя ТОЛЬКО если оно пришло в запросе (чтобы не затереть существующее "Гостем")
            if (!empty($data['client_name'])) {
                $updateData['name'] = $data['client_name'];
            }

            // День рождения обновляем всегда, если он пришел
            if (!empty($data['client_birthday'])) {
                $updateData['birthday'] = $data['client_birthday'];
            }

            // Если есть что обновлять — обновляем
            if (!empty($updateData)) {
                $guest->update($updateData);
            }

            $data['guest_id'] = $guest->id;
//        }

        // 2. СКЛЕЙКА ДАТЫ И ВРЕМЕНИ
        if (isset($data['date_only']) && isset($data['time_only'])) {
            $data['datetime'] = $data['date_only'] . ' ' . $data['time_only'] . ':00';
        }

        // 3. ЧИСТКА МУСОРА (Удаляем поля, которых нет в таблице records)
        unset($data['date_only']);
        unset($data['time_only']);
        unset($data['client_phone']);
        unset($data['client_name']);
        unset($data['client_birthday']);
        unset($data['warning_alert']);

        return $data;
    }
}
