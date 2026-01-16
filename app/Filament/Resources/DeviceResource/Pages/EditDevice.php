<?php

namespace App\Filament\Resources\DeviceResource\Pages;

use App\Filament\Resources\DeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDevice extends EditRecord
{
    protected static string $resource = DeviceResource::class;

    protected static ?string $title = 'Редагувати пристрій';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Видалити пристрій'),
        ];
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Зберегти зміни');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Скасувати');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Видаляємо parts з даних, щоб не намагатися зберегти їх як звичайні поля
        unset($data['parts']);
        return $data;
    }

    protected function afterSave(): void
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
    }
}
