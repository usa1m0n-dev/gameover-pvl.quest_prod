<?php

namespace App\Filament\Resources\PartyRoomResource\Pages;

use App\Filament\Resources\PartyRoomResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartyRooms extends ListRecords
{
    protected static string $resource = PartyRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
