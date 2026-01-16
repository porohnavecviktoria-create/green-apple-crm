<?php

namespace App\Filament\Resources\RepairResource\Pages;

use App\Filament\Resources\RepairResource;
use App\Models\Part;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditRepair extends EditRecord
{
    protected static string $resource = RepairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Заповнюємо parts для відображення в формі
        $repair = $this->record;
        $parts = [];
        
        foreach ($repair->parts as $part) {
            $parts[] = [
                'part_id' => $part->id,
                'quantity' => $part->pivot->quantity,
                'cost_per_unit' => $part->pivot->cost_per_unit,
            ];
        }
        
        $data['parts'] = $parts;
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
        
        // Зберігаємо дані про деталі для afterSave
        $this->partsData = $partsData;
        
        return $data;
    }

    protected $partsData = [];

    protected function afterSave(): void
    {
        $repair = $this->record;
        $originalParts = $repair->parts()->pluck('parts.id')->toArray();
        
        // Отримуємо нові деталі
        $newParts = [];
        if (!empty($this->partsData)) {
            foreach ($this->partsData as $partData) {
                if (!empty($partData['part_id'])) {
                    $newParts[$partData['part_id']] = [
                        'quantity' => (int) ($partData['quantity'] ?? 1),
                        'cost_per_unit' => (float) ($partData['cost_per_unit'] ?? 0),
                    ];
                }
            }
        }
        
        // Видаляємо старі деталі (повертаємо на склад)
        $partsToRemove = array_diff($originalParts, array_keys($newParts));
        foreach ($partsToRemove as $partId) {
            $part = Part::find($partId);
            if ($part && $repair->parts()->where('parts.id', $partId)->exists()) {
                $pivot = $repair->parts()->where('parts.id', $partId)->first()->pivot;
                $quantityToReturn = $pivot->quantity ?? 0;
                $part->increment('quantity', $quantityToReturn);
                $repair->parts()->detach($partId);
            }
        }
        
        // Додаємо нові деталі (списуємо зі складу)
        $partsToAdd = array_diff(array_keys($newParts), $originalParts);
        foreach ($partsToAdd as $partId) {
            if (isset($newParts[$partId])) {
                $part = Part::find($partId);
                if ($part) {
                    $quantityToUse = $newParts[$partId]['quantity'];
                    $costPerUnit = $newParts[$partId]['cost_per_unit'];
                    
                    // Додаємо зв'язок
                    $repair->parts()->attach($partId, [
                        'quantity' => $quantityToUse,
                        'cost_per_unit' => $costPerUnit,
                    ]);
                    
                    // Списуємо зі складу
                    $part->decrement('quantity', $quantityToUse);
                    
                    if ($part->quantity <= 0 && $part->status === 'Stock') {
                        $part->update(['quantity' => 0]);
                    }
                }
            }
        }
        
        // Оновлюємо кількість для змінених деталей
        $partsToUpdate = array_intersect(array_keys($newParts), $originalParts);
        foreach ($partsToUpdate as $partId) {
            if (isset($newParts[$partId])) {
                $part = Part::find($partId);
                if ($part) {
                    $pivot = $repair->parts()->where('parts.id', $partId)->first()->pivot;
                    $oldQuantity = $pivot->quantity ?? 0;
                    $newQuantity = $newParts[$partId]['quantity'];
                    $quantityDiff = $newQuantity - $oldQuantity;
                    
                    // Оновлюємо pivot
                    $repair->parts()->updateExistingPivot($partId, [
                        'quantity' => $newQuantity,
                        'cost_per_unit' => $newParts[$partId]['cost_per_unit'],
                    ]);
                    
                    if ($quantityDiff > 0) {
                        // Потрібно списати більше
                        $part->decrement('quantity', $quantityDiff);
                    } elseif ($quantityDiff < 0) {
                        // Потрібно повернути на склад
                        $part->increment('quantity', abs($quantityDiff));
                    }
                }
            }
        }
    }
}
