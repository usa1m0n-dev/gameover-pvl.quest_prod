<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Привязка брелока: Ожидание</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .loader {
            border-top-color: #6366f1;
            animation: spinner 1.5s linear infinite;
        }
        .ping {
            animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        @keyframes spinner {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-50 antialiased dark:bg-gray-950 text-gray-900 dark:text-white">

<div class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-md">

        <div class="bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl overflow-hidden p-8 text-center transition-all">

            <div id="icon-container" class="mb-6 flex justify-center relative h-16 w-16 mx-auto">

                <div id="icon-searching" class="absolute inset-0 text-indigo-500">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-20 ping"></span>
                    <svg class="w-16 h-16 relative" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" />
                    </svg>
                </div>

                <div id="icon-waiting-tag" class="hidden absolute inset-0 text-indigo-500">
                    <div class="loader w-16 h-16 border-4 border-gray-200 rounded-full"></div>
                </div>

                <div id="icon-success" class="hidden absolute inset-0 text-green-500">
                    <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <div id="icon-error" class="hidden absolute inset-0 text-red-500">
                    <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>

            <h2 id="status-text" class="text-xl font-semibold mb-2 text-indigo-600 dark:text-indigo-400">Установка связи...</h2>
            <p id="status-subtext" class="text-sm text-gray-500 dark:text-gray-400 mb-8">
                Ожидаем ответ от считывателя "{{ $keychain_pending->point->name }}"
            </p>

            <div id="action-buttons" class="hidden space-y-3">
                <a href="{{ url('/admin/guests/' . $keychain_pending->guest_id . '/edit') }}"
                   class="block w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                    Вернуться к гостю
                </a>
                <a href="{{ route('keychain.make.first', ['guest' => $keychain_pending->guest_id]) }}"
                   class="block w-full py-2.5 px-4 bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/20 font-medium rounded-lg transition">
                    Повторить попытку
                </a>
            </div>

        </div>
    </div>
</div>

<script>
    const pendingId = "{{ $keychain_pending->id }}";
    const statusText = document.getElementById('status-text');
    const statusSubtext = document.getElementById('status-subtext');
    const actionButtons = document.getElementById('action-buttons');

    // Иконки
    const iconSearching = document.getElementById('icon-searching');
    const iconWaitingTag = document.getElementById('icon-waiting-tag');
    const iconSuccess = document.getElementById('icon-success');
    const iconError = document.getElementById('icon-error');

    function hideAllIcons() {
        iconSearching.classList.add('hidden');
        iconWaitingTag.classList.add('hidden');
        iconSuccess.classList.add('hidden');
        iconError.classList.add('hidden');
    }

    function checkStatus() {
        fetch(`/keychain_status/${pendingId}`)
            .then(response => response.json())
            .then(data => {

                if (data.status === "Считыватель ожидает брелок") {
                    // Железо ответило, ждем метку
                    hideAllIcons();
                    iconWaitingTag.classList.remove('hidden');
                    statusText.innerText = "Считыватель готов";
                    statusText.className = "text-xl font-semibold mb-2 text-gray-900 dark:text-white";
                    statusSubtext.innerText = "Поднесите брелок к сканеру.";
                }
                else if (data.status === "Считано успешно") {
                    // Успех
                    showFinished('success', 'Успех!', `Брелок привязан. ID: ${data.hwid}`);
                }
                else if (data.status === "Считыватель оффлайн") {
                    // Таймаут (10 сек прошло)
                    showFinished('error', 'Ошибка связи', 'Считыватель не ответил вовремя. Проверьте питание или Wi-Fi.');
                }
                else if (data.status === "Ошибка считывания") {
                    // Hardware error
                    showFinished('error', 'Сбой чтения', 'Метка прочиталась с ошибкой. Попробуйте еще раз.');
                }
            })
            .catch(err => console.error('Ошибка опроса:', err));
    }

    function showFinished(type, title, message) {
        clearInterval(polling); // Вырубаем опросник
        hideAllIcons();
        actionButtons.classList.remove('hidden');

        statusText.innerText = title;
        statusSubtext.innerText = message;

        if (type === 'success') {
            iconSuccess.classList.remove('hidden');
            statusText.className = "text-xl font-semibold mb-2 text-green-600 dark:text-green-400";
        } else {
            iconError.classList.remove('hidden');
            statusText.className = "text-xl font-semibold mb-2 text-red-600 dark:text-red-400";
        }
    }

    // Запускаем поллинг раз в 1 секунду
    const polling = setInterval(checkStatus, 1000);
</script>
</body>
</html>