<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\KeychainPending;
use App\Models\Point;
use Illuminate\Http\Request;

class GuestKeychainController extends Controller
{
    /**
     * Получение информации о госте по считанному HWID (например, для проверки на кассе)
     */
    public function get($hwid)
    {
        $guest = Guest::where('keychain_hwid', $hwid)->first();

        if(!$guest) {
            return response()->json(['error' => 'Guest not found'], 404);
        }

        return view('guest_discount', [
            'name' => $guest->name,
            'discount' => $guest->keychain_personal_discount
        ]);
    }

    /**
     * Шаг 1: Выбор точки (считывателя) для привязки метки
     */
    public function make_keychain_first(Guest $guest)
    {
        // Лучше брать id и name, чтобы в select (на view) было value="{{ $point->id }}"
        $points = Point::select('id', 'name')->get();

        return view('make_keychain_first', [
            'points' => $points,
            'guest_id' => $guest->id
        ]);
    }

    /**
     * Шаг 2: Создание задачи для NodeMCU
     */
    public function make_keychain_second(Guest $guest, Point $point)
    {
        // Перед созданием новой задачи можно удалять/закрывать старые зависшие задачи для этой точки
        KeychainPending::where('point_id', $point->id)
            ->whereIn('status', ['Ожидает ответ от считывателя', 'Считыватель ожидает брелок'])
            ->update(['status' => 'Отменено таймаутом']);

        $keychain_pending = KeychainPending::create([
            'point_id' => $point->id,
            'guest_id' => $guest->id,
            'status' => 'Ожидает ответ от считывателя' // Дефолтный статус
        ]);

        return view('make_keychain_second', ['keychain_pending' => $keychain_pending]);
    }

    /**
     * Endpoint для NodeMCU: проверка, есть ли активная задача на считывание (GET)
     */
    public function check_pending(Point $point)
    {
        // Ищем задачу, которая либо только создана, либо уже в процессе считывания
        $pending = KeychainPending::where('point_id', $point->id)
            ->whereIn('status', ['Ожидает ответ от считывателя', 'Считыватель ожидает брелок'])
            ->first();

        if(!$pending) {
            // Возвращаем пустой ответ или команду спать, если задач нет
            return response()->json(['action' => 'idle']);
        }

        return response()->json([
            'action' => 'read',
            'guest_id' => $pending->guest_id,
            'point_id' => $point->id
        ]);
    }

    /**
     * Endpoint для NodeMCU: отправка результата считывания (POST)
     */
    public function set_status(Request $request, Point $point)
    {
        // Валидируем пришедший от ESP8266 JSON payload
        $validated = $request->validate([
            'code' => 'required|integer',
            'hwid' => 'string|nullable'
        ]);

        // Ищем активную задачу
        $pending = KeychainPending::where('point_id', $point->id)
            ->whereIn('status', ['Ожидает ответ от считывателя', 'Считыватель ожидает брелок'])
            ->first();

        if(!$pending) {
            return response()->json(['error' => 'No active pending request'], 404);
        }

        switch($validated['code']){
            case 0:
                $pending->status = "Считыватель ожидает брелок";
                break;
            case 1:
                $pending->status = "Считано успешно";
                $pending->hwid = $validated['hwid'];

                // Сразу привязываем HWID к гостю, чтобы не делать лишних шагов
                $this->append_hwid_to_guest($pending->guest, $validated['hwid']);
                break;
            case 2:
                $pending->status = "Ошибка считывания";
                break;
            default:
                return response()->json(['error' => 'Invalid code'], 400);
        }

        $pending->save();
        return response()->json(['status' => 'OK']);
    }

    /**
     * Метод привязки HWID к гостю (с защитой от дублирования меток)
     */
    public function append_hwid_to_guest(Guest $guest, $hwid)
    {
        // Проверяем, не привязан ли уже этот брелок к кому-то другому
        $existingGuest = Guest::where('keychain_hwid', $hwid)->first();

        if ($existingGuest && $existingGuest->id !== $guest->id) {
            // Если метка была у Васи, а теперь мы даем ее Пете - отвязываем от Васи
            $existingGuest->update(['keychain_hwid' => null]);
        }

        // Привязываем метку целевому гостю
        $guest->update(['keychain_hwid' => $hwid]);

        return response()->json(['success' => true, 'guest' => $guest->name]);
    }
    public function get_pending_status(KeychainPending $keychain_pending)
    {
        // Если статус начальный, проверяем сколько времени прошло
        if ($keychain_pending->status === 'Ожидает ответ от считывателя') {
            // Если прошло больше 10 секунд — отменяем по таймауту
            if ($keychain_pending->created_at->diffInSeconds(now()) > 10) {
                $keychain_pending->status = 'Считыватель оффлайн';
                $keychain_pending->save();
            }
        }

        return response()->json([
            'status' => $keychain_pending->status,
            'hwid' => $keychain_pending->hwid,
        ]);
    }
}