<?php

namespace App\Filament\Resources\FastEntryResource\Pages;

use App\Filament\Resources\FastEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFastEntry extends EditRecord
{
    protected static string $resource = FastEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
