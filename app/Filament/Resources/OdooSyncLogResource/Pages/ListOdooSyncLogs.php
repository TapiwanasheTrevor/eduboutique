<?php

namespace App\Filament\Resources\OdooSyncLogResource\Pages;

use App\Filament\Resources\OdooSyncLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOdooSyncLogs extends ListRecords
{
    protected static string $resource = OdooSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
