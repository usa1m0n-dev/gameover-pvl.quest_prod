<?php

namespace App\Filament\Resources\InventoryLogResource\Pages;

use App\Filament\Resources\InventoryLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventoryLog extends EditRecord
{
    protected static string $resource = InventoryLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
