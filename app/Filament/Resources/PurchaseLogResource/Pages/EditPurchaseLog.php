<?php

namespace App\Filament\Resources\PurchaseLogResource\Pages;

use App\Filament\Resources\PurchaseLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseLog extends EditRecord
{
    protected static string $resource = PurchaseLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
