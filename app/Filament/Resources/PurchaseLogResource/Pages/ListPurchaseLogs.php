<?php

namespace App\Filament\Resources\PurchaseLogResource\Pages;

use App\Filament\Resources\PurchaseLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseLogs extends ListRecords
{
    protected static string $resource = PurchaseLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
