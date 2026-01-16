<?php

namespace App\Filament\Resources\RepairResource\Pages;

use App\Filament\Resources\RepairResource;
use App\Models\Part;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateRepair extends CreateRecord
{
    protected static string $resource = RepairResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Розраховуємо собівартість деталей та прибуток
        $partsCost = 0;
        $partsData = $data['parts'] ?? [];
        
        // Видаляємо parts з data, щоб не намагатися зберегти їх напряму
        unset($data['parts']);
        
        if (!empty($partsData)) {
            foreach ($partsData as $partData) {
                if (!empty($partData['part_id']) && !empty($partData['quantity']) && !empty($partData['cost_per_unit'])) {
                    $partsCost += ($partData['cost_per_unit'] * $partData['quantity']);
                }
            }
        }
        
        $repairCost = (float) ($data['repair_cost'] ?? 0);
        $profit = $repairCost - $partsCost;
        
        $data['parts_cost'] = round($partsCost, 2);
        $data['profit'] = round($profit, 2);
        
        // Зберігаємо дані про деталі для afterCreate
        $this->partsData = $partsData;
        
        return $data;
    }

    protected $partsData = [];

    protected function afterCreate(): void
    {
        $repair = $this->record;
        
        // Додаємо зв'язки з деталями вручну
        if (!empty($this->partsData)) {
            foreach ($this->partsData as $partData) {
                if (!empty($partData['part_id']) && !empty($partData['quantity']) && !empty($partData['cost_per_unit'])) {
                    $partId = $partData['part_id'];
                    $quantity = (int) $partData['quantity'];
                    $costPerUnit = (float) $partData['cost_per_unit'];
                    
                    // Додаємо зв'язок в pivot таблицю
                    $repair->parts()->attach($partId, [
                        'quantity' => $quantity,
                        'cost_per_unit' => $costPerUnit,
                    ]);
                    
                    // Списуємо деталі зі складу
                    $part = Part::find($partId);
                    if ($part) {
                        $part->decrement('quantity', $quantity);
                        
                        // Якщо кількість стала 0, можна змінити статус (опціонально)
                        if ($part->quantity <= 0 && $part->status === 'Stock') {
                            $part->update(['quantity' => 0]);
                        }
                    }
                }
            }
            
            Notification::make()
                ->title('Деталі списано зі складу')
                ->success()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
