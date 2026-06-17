<?php

namespace App\Services;

use App\Models\Record;
use Carbon\Carbon;

class SlotSuggester
{
    // Полный список по твоему запросу
    protected const SLOTS = [
        '00:00', '00:30', '01:00', '01:30', '02:00', '02:30', '03:00',
        '09:00', '09:30', '10:00', '10:30',
        '11:00', '11:30', // VIP Утро
        '12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
        '15:00', '15:30', // VIP День
        '16:00', '16:30', '17:00', '17:30',
        '18:00', '18:30', // VIP Вечер
        '19:00', '19:30', '20:00', '20:30',
        '21:00', '21:30', '22:00', '22:30', '23:00', '23:30'
    ];

    // Какие часы считаем VIP (начало слота)
    protected const VIP_HOURS = [
        '11:00', '11:30',
        '15:00', '15:30',
        '18:30', '19:00'
    ];

    public static function suggest(string $date, int $pointId): array
    {
        return [];
        $suggestions = [];
        $carbonDate = Carbon::parse($date);
        $isToday = $carbonDate->isToday();
        $now = now();

        // 1. Получаем занятые слоты
        $busyRecords = Record::query()
            ->whereDate('datetime', $date)
            ->where('point_id', $pointId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('datetime')
            ->get();

        // Формируем интервалы [Start, End] (игра 60 мин)
        $busyIntervals = $busyRecords->map(function ($rec) {
            $start = Carbon::parse($rec->datetime);
            // Берем реальную длительность или 60 мин
            $duration = $rec->estimated_room_time > 0 ? 60 : 60;
            return [
                'start' => $start,
                'end' => $start->copy()->addMinutes($duration),
            ];
        });

        // 2. Проходим по ВСЕМУ твоему списку
        foreach (self::SLOTS as $time) {
            $slotStart = Carbon::parse($date . ' ' . $time);
            $slotEnd = $slotStart->copy()->addMinutes(60); // Предполагаем игру на час

            // --- ФИЛЬТР 1: ВРЕМЯ (Если сегодня) ---
            if ($isToday) {
                if ($slotStart->lte($now)) continue; // Прошло
                // Правило 2 часов: если до слота меньше 1.5 часов - не предлагаем (не успеем собрать)
                if ($slotStart->diffInMinutes($now) < 90) continue;
            }

            // --- ФИЛЬТР 2: ПЕРЕСЕЧЕНИЕ ---
            $isBusy = false;
            foreach ($busyIntervals as $interval) {
                // Если наш слот (Start-End) наезжает на занятый интервал
                if ($slotStart->lt($interval['end']) && $slotEnd->gt($interval['start'])) {
                    $isBusy = true;
                    break;
                }
            }
            if ($isBusy) continue;

            // --- АНАЛИЗ КАЧЕСТВА СЛОТА ---

            $status = 'good';
            $reason = 'Свободно';
            $score = 10; // Базовый балл

            $isVip = in_array($time, self::VIP_HOURS);
            if ($isVip) {
                $score += 5; // VIP слоты всегда чуть выше
                $reason = 'VIP время';
            }

            // Если день НЕ пустой, анализируем "Дыры" (Gaps)
            if ($busyIntervals->isNotEmpty()) {
                $minGapMinutes = 9999;
                $closestRecord = null;

                foreach ($busyIntervals as $interval) {
                    // Разрыв: Игра заканчивается -> Наш слот начинается
                    if ($interval['end']->lte($slotStart)) {
                        $gap = $interval['end']->diffInMinutes($slotStart);
                        $minGapMinutes = min($minGapMinutes, $gap);
                    }
                    // Разрыв: Наш слот заканчивается -> Игра начинается
                    if ($slotEnd->lte($interval['start'])) {
                        $gap = $slotEnd->diffInMinutes($interval['start']);
                        $minGapMinutes = min($minGapMinutes, $gap);
                    }
                }

                // Логика оценки разрыва
                if ($minGapMinutes <= 30) {
                    // ИДЕАЛЬНО: Встаем встык или с перерывом 30 мин
                    $status = 'optimal';
                    $reason = 'Встает плотно к графику 🔥';
                    $score += 20; // Огромный бонус
                } elseif ($minGapMinutes <= 90) {
                    // НОРМАЛЬНО: Окно до 1.5 часов
                    $status = 'good';
                    $reason = 'Удобное окно';
                    $score += 10;
                } else {
                    // ПЛОХО: Дыра больше 1.5 часов (простой персонала)
                    // Но если это утро (до 12) или поздний вечер, штраф меньше
                    $hour = $slotStart->hour;
                    if ($hour > 12 && $hour < 21) {
                        $status = 'warning';
                        $reason = 'Создает простой (>1.5ч) ⚠️';
                        $score -= 15; // Штрафуем, чтобы ушло вниз списка
                    } else {
                        $reason = 'Свободное время';
                    }
                }
            } else {
                // День пустой - всё optimal, но VIP выше
                if ($isVip) {
                    $status = 'optimal';
                    $reason = 'Лучшее время для старта (VIP)';
                }
            }

            $suggestions[] = [
                'time' => $time,
                'reason' => $reason,
                'status' => $status,
                'score' => $score,
                'is_vip' => $isVip,
            ];
        }

        // СОРТИРОВКА:
        // 1. Сначала по баллам (Optimal и VIP наверху)
        // 2. Если баллы равны, то по времени (утренние раньше)
        usort($suggestions, function($a, $b) {
            if ($a['score'] === $b['score']) {
                return strcmp($a['time'], $b['time']);
            }
            return $b['score'] <=> $a['score'];
        });

        return $suggestions;
    }
}
