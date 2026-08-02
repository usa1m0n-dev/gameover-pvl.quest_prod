<?php

namespace App\Filament\Pages;

use App\Models\FastEntry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class FastDataEntry extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Быстрый ввод';
    protected static ?string $title = 'Потоковый ввод данных';
    protected static string $view = 'filament.pages.fast-data-entry';
    protected static ?string $navigationGroup = 'Клиенты';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'visit_date' => now()->toDateString(), // По умолчанию ставим сегодня
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Имя')
                    ->placeholder('Иван')
                    ->extraInputAttributes(['id' => 'first-input-field']) // ID для автофокуса
                    ->maxLength(255),

                PhoneInput::make('phone')
                    ->label('Номер телефона')
                    ->required()
                    ->defaultCountry('KZ') // Задаем дефолт на Казахстан (+7)
                    ->displayNumberFormat(PhoneInputNumberType::INTERNATIONAL),

                DatePicker::make('visit_date')
                    ->label('Дата посещения')
                    ->default(now()),
            ])
            ->statePath('data')
            ->columns(3); // Размещаем в одну строку для удобства оператора
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Умное форматирование телефона "на лету" (защита от дурака)
        $phone = $this->normalizePhone($state['phone']);

        // Сохраняем в БД
        FastEntry::create([
            'name' => $state['name'] ?? null,
            'phone' => $phone,
            'visit_date' => $state['visit_date'] ?? null,
        ]);

        // Очищаем форму, но оставляем текущую дату, чтобы не вводить её заново
        $this->form->fill([
            'visit_date' => $state['visit_date'] ?? now()->toDateString(),
        ]);

        // Кидаем тихий и быстрый тост об успехе
        Notification::make()
            ->success()
            ->title('Сохранено')
            ->duration(1500) // Быстро исчезает, чтобы не спамить экран
            ->send();

        // Вызываем JS событие для возврата фокуса
        $this->dispatch('focus-first-field');
    }

    private function normalizePhone(string $phone): string
    {
        // Убираем все, кроме цифр и плюса
        $clean = preg_replace('/[^0-9+]/', '', $phone);

        // Если оператор начал вводить с 8 (типично для КЗ/РФ), меняем на +7
        if (str_starts_with($clean, '8') && strlen($clean) === 11) {
            return '+7' . substr($clean, 1);
        }

        // Если ввел просто 7 без плюса
        if (str_starts_with($clean, '7') && strlen($clean) === 11) {
            return '+' . $clean;
        }

        return $clean;
    }
}
