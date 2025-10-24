<?php

namespace App\Filament\Resources\OdooSyncLogResource\Pages;

use App\Filament\Resources\OdooSyncLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOdooSyncLog extends ViewRecord
{
    protected static string $resource = OdooSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
