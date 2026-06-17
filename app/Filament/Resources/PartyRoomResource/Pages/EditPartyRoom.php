<?php

namespace App\Filament\Resources\PartyRoomResource\Pages;

use App\Filament\Resources\PartyRoomResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartyRoom extends EditRecord
{
    protected static string $resource = PartyRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
