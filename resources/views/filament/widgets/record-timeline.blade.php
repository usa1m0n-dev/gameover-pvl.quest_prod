<x-filament-widgets::widget>
    <div class="flex flex-col lg:flex-row gap-6 h-full">

        <div class="w-full lg:w-1/3 flex flex-col">
            <div class=" flex flex-col bg-white dark:bg-gray-900 p-4 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="flex items-center justify-between mb-4">
                <button wire:click="prevMonth" class="p-1 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 rounded-full transition">
                    <x-heroicon-m-chevron-left class="w-5 h-5" />
                </button>
                <div class="text-lg font-bold capitalize text-gray-950 dark:text-white">
                    {{ \Carbon\Carbon::parse($currentMonth)->translatedFormat('F Y') }}
                </div>
                <button wire:click="nextMonth" class="p-1 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 rounded-full transition">
                    <x-heroicon-m-chevron-right class="w-5 h-5" />
                </button>
            </div>

            <div class="grid grid-cols-7 mb-2 text-center border-b border-gray-200 dark:border-gray-700 pb-2">
                @foreach(['Пн','Вт','Ср','Чт','Пт','Сб','Вс'] as $day)
                    <div class="text-xs font-semibold text-gray-400 uppercase">{{ $day }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7 gap-1">
                @foreach($this->getCalendarDays() as $day)
                    <button wire:click="selectDate('{{ $day['date'] }}')"
                            class="relative h-10 w-full rounded-lg flex items-center justify-center text-sm transition
                        {{ $day['isSelected'] ? 'bg-primary-600 text-white font-bold shadow' : 'hover:bg-gray-50 dark:hover:bg-white/5 text-gray-700 dark:text-gray-200' }}
                        {{ $day['isToday'] && !$day['isSelected'] ? 'ring-1 ring-primary-500 text-primary-600 font-semibold' : '' }}">

                        {{ $day['day'] }}

                        @if($day['hasEvents'])
                            <span class="absolute bottom-1.5 w-1 h-1 rounded-full {{ $day['isSelected'] ? 'bg-white' : 'bg-green-500' }}"></span>
                        @endif
                    </button>
                @endforeach
            </div>

            <button wire:click="goToToday" class="mt-6 w-full py-2 text-sm text-gray-500 border border-dashed border-gray-300 rounded-lg hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5">
                Вернуться к сегодня
            </button>
            </div>
        </div>

        <div class="w-full lg:w-2/3 bg-white dark:bg-gray-900 p-5 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 flex flex-col">

            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 border-b border-gray-100 dark:border-gray-800 pb-4 p-2">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y, l') }}
                </h2>

                <div class="flex items-center gap-2 bg-gray-50 dark:bg-white/5 px-3 py-1.5 rounded-lg">
                    <x-heroicon-m-map-pin class="w-5 h-5 text-gray-400" />

                    <div class="flex flex-wrap gap-2">
                        @foreach(\App\Models\Point::all() as $point)
                            <button
                                    type="button"
                                    wire:click.prevent="setPointId({{ $point->id }})"
                                    class="px-3 py-1 rounded-full text-sm font-semibold transition ease-in-out duration-150
                    {{ $pointId == $point->id
                        ? 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
                        : 'bg-blue-600 text-white shadow-md'

                    }}"
                            >
                                {{ $point->name }}
                            </button>
                        @endforeach
                    </div>

                    <input type="hidden" wire:model.live="pointId">
                </div>

                {{-- В вашем Livewire-компоненте --}}
                {{-- public function setPointId($id) { $this->pointId = $id; } --}}
            </div>

            @php
                $suggestions = $this->getSuggestions();
                $bestSuggestions = collect($suggestions)->take(4); // Топ-4
            @endphp

            @if($bestSuggestions->isNotEmpty())
                <div class="mb-6">
                    <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-1">
                        <x-heroicon-m-sparkles class="w-4 h-4 text-yellow-500" />
                        Рекомендуемое время
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach($bestSuggestions as $slot)
                            <a href="{{ \App\Filament\Resources\RecordResource::getUrl('create', [
                                    'date' => $date,
                                    'time' => $slot['time'],
                                    'point_id' => $pointId
                                ]) }}"
                               class="flex flex-col p-3 rounded-lg border transition group relative overflow-hidden
                                {{ $slot['status'] === 'optimal'
                                    ? 'bg-green-50 border-green-200 hover:bg-green-100 dark:bg-green-900/20 dark:border-green-800 dark:hover:bg-green-900/30'
                                    : ($slot['status'] === 'warning' ? 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800 opacity-70' : 'bg-gray-50 border-gray-200 hover:bg-gray-100 dark:bg-white/5 dark:border-white/10 dark:hover:bg-white/10') }}
                                "
                            >
                                <div class="flex justify-between items-center z-10">
                                    <span class="font-bold text-lg text-gray-900 dark:text-white">{{ $slot['time'] }}</span>
                                    @if($slot['is_vip']) <span title="VIP">👑</span> @endif
                                </div>
                                <div class="text-[10px] leading-tight mt-1 z-10
                                    {{ $slot['status'] === 'optimal' ? 'text-green-700 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $slot['reason'] }}
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="relative border-l-2 border-gray-200 dark:border-gray-700 ml-3 space-y-4 p-2">
                <div class="flex justify-between items-center mb-4">
                    <x-filament::button
                            color="warning"
                            tag="a"
                            href="{{ \App\Filament\Pages\FillShift::getUrl(['point_id' => $this->pointId, 'date' => $this->date]) }}"
                    >
                        Записать смену
                    </x-filament::button>
                </div>
                @forelse($this->getRecords() as $record)
                    <div class="relative pl-8 group">
                        <div class="absolute -left-[9px] top-5 w-4 h-4 rounded-full bg-white dark:bg-gray-800 border-4 shadow-sm z-10
                            {{ $record->status === 'paid' ? 'border-green-500' : ($record->status === 'new' ? 'border-gray-400' : 'border-blue-500') }}"></div>

                        <a href="{{ \App\Filament\Resources\RecordResource::getUrl('edit', ['record' => $record]) }}"
                           class="block p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-transparent hover:border-gray-200 dark:hover:border-white/20 transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xl text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($record->datetime)->format('H:i') }}</span>
                                        @if($record->room)
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300">{{$record->room->name}} - {{ $record->estimated_room_time }}ч</span>
                                        @endif
                                        @if($record->note)
        <div class="mt-1 px-1.5 py-0.5 text-xs font-semibold text-amber-700 bg-amber-100 border border-amber-200 rounded flex items-start gap-1">
            <svg class="w-3 h-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            
            <span class="leading-tight">{{ $record->note }}</span>
        </div>
    @endif
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1 flex items-center gap-1">
                                        <span>{{ $record->guest->name }}</span>
                                        <span class="opacity-50 text-xs">{{ $record->guest->phone }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ number_format($record->total, 0, '.', ' ') }} ₸</div>
                                    <div class="text-[10px] font-bold uppercase tracking-wider mt-1 {{ $record->status === 'paid' ? 'text-green-600' : 'text-blue-600' }}">{{ $record->status }}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="pl-8 py-12 text-center text-gray-400 dark:text-gray-600">
                        Записей нет.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
