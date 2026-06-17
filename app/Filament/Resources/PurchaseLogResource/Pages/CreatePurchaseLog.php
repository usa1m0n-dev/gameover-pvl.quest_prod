<?php

namespace App\Filament\Resources\PurchaseLogResource\Pages;

use App\Filament\Resources\PurchaseLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseLog extends CreateRecord
{
    protected static string $resource = PurchaseLogResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
