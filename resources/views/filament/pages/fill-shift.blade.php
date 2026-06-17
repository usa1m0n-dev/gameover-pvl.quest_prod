<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" size="lg">
                Записать смену для всех игр
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>