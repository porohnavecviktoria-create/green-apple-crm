<?php

namespace App\Filament\Resources\PartResource\Pages;

use App\Filament\Resources\PartResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListParts extends ListRecords
{
    protected static string $resource = PartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Додати запчастину')
                ->createAnother(false)
                ->action(function (array $data, Actions\CreateAction $action): void {
                    // Визначаємо статус залежно від таба
                    $tab = $this->activeTab;
                    if ($tab === 'restoration') {
                        $data['status'] = 'Restore';
                    } elseif ($tab === 'broken') {
                        $data['status'] = 'Broken';
                    } else {
                        $data['status'] = 'Stock';
                    }

                    // Критерії пошуку існуючої запчастини (якщо немає серійника)
                    if (empty($data['serial_number'])) {
                        $existingPart = \App\Models\Part::where('name', $data['name'])
                            ->where('part_type_id', $data['part_type_id'])
                            ->where('status', $data['status'])
                            ->whereNull('serial_number')
                            ->first();

                        if ($existingPart) {
                            $oldQty = $existingPart->quantity;
                            $oldCost = $existingPart->cost_uah;
                            $newQty = (int) $data['quantity'];
                            $addCost = (float) $data['cost_uah'];

                            $totalQty = $oldQty + $newQty;
                            // Середньозважена ціна: ((3 * 100) + (5 * 200)) / 8
                            $newCost = (($oldQty * $oldCost) + ($newQty * $addCost)) / $totalQty;

                            $existingPart->update([
                                'quantity' => $totalQty,
                                'cost_uah' => round($newCost, 2),
                                'contractor_id' => $data['contractor_id'] ?? $existingPart->contractor_id,
                                'description' => trim(($existingPart->description ?? '') . "\nДодано {$newQty} шт. по {$addCost} грн (" . now()->format('d.m.Y') . ")")
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Запчастини об\'єднано! 🤝')
                                ->body("Кількість збільшена до {$totalQty}, середня ціна: " . round($newCost, 2) . " грн.")
                                ->success()
                                ->seconds(5)
                                ->send();

                            return;
                        }
                    }

                    // Якщо не знайшли або є серійник - створюємо нову
                    \App\Models\Part::create($data);

                    \Filament\Notifications\Notification::make()
                        ->title('Створено успішно! ✅')
                        ->success()
                        ->seconds(5)
                        ->send();
                }),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'stock';
    }

    public function getTabs(): array
    {
        return [
            'stock' => \Filament\Resources\Components\Tab::make('На складі')
                ->modifyQueryUsing(fn($query) => $query->where('status', 'Stock'))
                ->badge(\App\Models\Part::where('status', 'Stock')->count())
                ->icon('heroicon-o-archive-box'),
            'restoration' => \Filament\Resources\Components\Tab::make('Цех реставрації')
                ->modifyQueryUsing(fn($query) => $query->where('status', 'Restore'))
                ->badge(\App\Models\Part::where('status', 'Restore')->count())
                ->icon('heroicon-o-wrench-screwdriver'),
            'broken' => \Filament\Resources\Components\Tab::make('Брак / Списано')
                ->modifyQueryUsing(function ($query) {
                    return $query->where('status', 'Broken')
                        ->join('part_types', 'parts.part_type_id', '=', 'part_types.id')
                        ->select('parts.*')
                        ->orderBy('part_types.name')
                        ->orderBy('parts.name');
                })
                ->badge(\App\Models\Part::where('status', 'Broken')->count())
                ->icon('heroicon-o-x-circle'),
        ];
    }
}
