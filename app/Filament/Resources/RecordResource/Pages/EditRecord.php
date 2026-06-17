<?php

namespace App\Filament\Resources\RecordResource\Pages;

use App\Filament\Resources\RecordResource;
use App\Models\Guest;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord as BaseEditRecord;

class EditRecord extends BaseEditRecord
{
    protected static string $resource = RecordResource::class;

    // 1. ПРИ ОТКРЫТИИ: Разбиваем datetime на дату и время для формы
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['datetime'])) {
            $carbon = Carbon::parse($data['datetime']);
            $data['date_only'] = $carbon->format('Y-m-d');
            $data['time_only'] = $carbon->format('H:i');
        }

        return $data;
    }

    // 2. ПРИ СОХРАНЕНИИ: Склеиваем обратно
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 1. ЛОГИКА КЛИЕНТА
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
//            dd($updateData);
            // Если есть что обновлять — обновляем
            if (!empty($updateData)) {
                $guest->update($updateData);

            }

            $data['guest_id'] = $guest->id;
//        }

        // 2. ЛОГИКА ДАТЫ
        if (isset($data['date_only']) && isset($data['time_only'])) {
            $data['datetime'] = $data['date_only'] . ' ' . $data['time_only'] . ':00';
        }

        // 3. ЧИСТКА
        unset($data['date_only'], $data['time_only'], $data['client_phone'], $data['client_name'], $data['warning_alert']);

        return $data;
    }
}
