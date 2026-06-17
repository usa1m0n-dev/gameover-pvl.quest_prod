<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Привязка брелока</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 antialiased dark:bg-gray-950 text-gray-900 dark:text-white">

<div class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-2xl">

        <div class="bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl overflow-hidden">

            <div class="px-6 py-5 border-b border-gray-100 dark:border-white/10">
                <h2 class="text-xl font-semibold leading-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                    </svg>
                    Шаг 1: Выбор считывателя
                </h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Укажите локацию, на которой вы сейчас находитесь.
                </p>
            </div>

            <div class="p-6">
                @if(isset($points) && $points->count() > 0)
                    <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3">
                        @foreach($points as $point)
                            <a href="{{ route('keychain.make.second', ['guest' => $guest_id, 'point' => $point->id]) }}"
                               class="group flex flex-col items-center justify-center p-6 text-center transition-all duration-200 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl hover:bg-indigo-50 hover:border-indigo-500 dark:hover:bg-indigo-500/10 dark:hover:border-indigo-500 hover:ring-1 hover:ring-indigo-500 hover:-translate-y-0.5">

                                <svg class="w-10 h-10 mb-3 text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6m-7 3h8m-9 3h10m-9 3h8m-7 3h6m-9-15v18a2 2 0 002 2h10a2 2 0 002-2V3a2 2 0 00-2-2H7a2 2 0 00-2 2z" />
                                </svg>
                                <span class="text-sm font-medium">
                                        {{ $point->name }}
                                    </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-semibold">Нет доступных считывателей</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Добавьте точки (Points) в базу данных перед привязкой.</p>
                    </div>
                @endif
            </div>

            <div class="px-6 py-4 bg-gray-50 dark:bg-white/5 border-t border-gray-100 dark:border-white/10 flex items-center">
                <a href="{{ url()->previous() }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white flex items-center gap-1">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Отмена и возврат
                </a>
            </div>

        </div>
    </div>
</div>

</body>
</html>