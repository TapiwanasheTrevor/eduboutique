<?php

namespace App\Filament\Resources\OdooSyncLogResource\Pages;

use App\Filament\Resources\OdooSyncLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOdooSyncLog extends EditRecord
{
    protected static string $resource = OdooSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
