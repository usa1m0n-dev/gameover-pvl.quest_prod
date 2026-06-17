<x-filament-panels::page>
    @if ($viewMode === 'table')

        @if (count($tabs = $this->getTabs()))
            <div class="flex justify-center mb-4">
                <x-filament::tabs>
                    @foreach ($tabs as $tabKey => $tab)
                        <x-filament::tabs.item
                            :active="$activeTab === $tabKey"
                            :badge="$tab->getBadge()"
                            :badge-color="$tab->getBadgeColor()"
                            :icon="$tab->getIcon()"
                            wire:click="$set('activeTab', '{{ $tabKey }}')"
                        >
                            {{ $tab->getLabel() }}
                        </x-filament::tabs.item>
                    @endforeach
                </x-filament::tabs>
            </div>
        @endif

        {{ $this->table }}

    @endif
</x-filament-panels::page>
