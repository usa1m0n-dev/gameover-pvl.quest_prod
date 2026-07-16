<x-filament-panels::page>
    {{-- Оборачиваем форму. По нажатию Enter вызываем метод save --}}
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" size="lg">
                Сохранить (Enter)
            </x-filament::button>
        </div>
    </form>

    {{-- Скрипт для удержания темпа оператора --}}
    @script
    <script>
        // При загрузке страницы сразу ставим фокус
        setTimeout(() => {
            document.getElementById('first-input-field')?.focus();
        }, 100);

        // Слушаем событие из Livewire после успешного сохранения
        window.addEventListener('focus-first-field', () => {
            setTimeout(() => {
                document.getElementById('first-input-field')?.focus();
            }, 50); // Микрозадержка, чтобы DOM успел обновиться
        });
    </script>
    @endscript
</x-filament-panels::page>