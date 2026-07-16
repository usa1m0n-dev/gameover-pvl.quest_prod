<?php

namespace App\Filament\Resources\FastEntryResource\Pages;

use App\Filament\Resources\FastEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFastEntries extends ListRecords
{
    protected static string $resource = FastEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
