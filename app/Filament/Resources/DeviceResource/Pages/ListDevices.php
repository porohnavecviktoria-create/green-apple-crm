<?php

namespace App\Filament\Resources\DeviceResource\Pages;

use App\Filament\Resources\DeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDevices extends ListRecords
{
    protected static string $resource = DeviceResource::class;

    protected static ?string $title = 'Склад техніки';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_batch')
                ->label('📦 Додати партію')
                ->icon('heroicon-o-plus-circle')
                ->url(\App\Filament\Resources\BatchResource::getUrl('create'))
                ->color('success'),
            Actions\CreateAction::make()
                ->label('Додати новий пристрій'),
        ];
    }
}
