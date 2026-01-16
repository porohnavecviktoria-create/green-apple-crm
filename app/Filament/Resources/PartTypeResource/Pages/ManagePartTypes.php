<?php

namespace App\Filament\Resources\PartTypeResource\Pages;

use App\Filament\Resources\PartTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePartTypes extends ManageRecords
{
    protected static string $resource = PartTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
