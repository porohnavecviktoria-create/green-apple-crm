<?php

namespace App\Filament\Resources\DeviceResource\Pages;

use App\Filament\Resources\DeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDevice extends CreateRecord
{
    protected static string $resource = DeviceResource::class;

    protected static ?string $title = 'Додати пристрій';

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Зберегти та додати');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Додати та створити ще один');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Скасувати');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Видаляємо parts з даних, щоб не намагатися зберегти їх як звичайні поля
        unset($data['parts']);
        
        // Якщо imei порожнє - встановлюємо null
        if (empty($data['imei'])) {
            $data['imei'] = null;
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Синхронізуємо parts з pivot даними (quantity)
        $device = $this->record->fresh();
        $formData = $this->form->getState();
        $partsData = $formData['parts'] ?? [];
        
        if (!empty($partsData)) {
            $syncData = [];
            foreach ($partsData as $partData) {
                if (!empty($partData['part_id'])) {
                    $syncData[$partData['part_id']] = [
                        'quantity' => (int)($partData['quantity'] ?? 1)
                    ];
                }
            }
            $device->parts()->sync($syncData);
        }
        
        // Списуємо кількість запчастин зі складу
        $device->load('parts');
        foreach ($device->parts as $part) {
            $quantity = $part->pivot->quantity ?? 1;
            $part->decrement('quantity', $quantity);
        }
    }
}
