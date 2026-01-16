<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use App\Filament\Resources\ConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageConsumables extends ManageRecords
{
    protected static string $resource = ConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Додати розхідник'),
        ];
    }
}
