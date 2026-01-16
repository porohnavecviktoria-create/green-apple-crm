<?php

namespace App\Filament\Resources\AccessoryResource\Pages;

use App\Filament\Resources\AccessoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAccessories extends ManageRecords
{
    protected static string $resource = AccessoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Додати аксесуар'),
        ];
    }
}
