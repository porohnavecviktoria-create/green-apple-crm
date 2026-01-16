<?php

namespace App\Filament\Widgets;

use App\Models\Device;
use App\Models\Part;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class CurrentStockWidget extends Widget
{
    protected static string $view = 'filament.widgets.current-stock-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 4;
    
    public function getStockData(): array
    {
        // Техніка на складі (зараз)
        $devicesValue = (float) Device::where('status', 'Stock')
            ->sum('purchase_cost');
        
        // Деталі на складі (Part з типом "Деталь", не Аксесуар/Інвентар/Розхідник)
        $partsValue = (float) Part::whereHas('partType', function ($query) {
                $query->where('name', 'not like', '%Аксесуар%')
                    ->where('name', 'not like', '%Інвентар%')
                    ->where('name', 'not like', '%Розхідник%')
                    ->where('name', 'not like', '%Викрутка%')
                    ->where('name', 'not like', '%Паяльник%')
                    ->where('name', 'not like', '%Переклей%')
                    ->where('name', 'not like', '%Чохол%');
            })
            ->where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        // Інвентар на складі
        $inventoryValue = (float) Part::whereHas('partType', function ($query) {
                $query->where('name', 'like', '%Інвентар%');
            })
            ->where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        // Розхідники на складі
        $consumablesValue = (float) Part::whereHas('partType', function ($query) {
                $query->where('name', 'like', '%Розхідник%');
            })
            ->where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        return [
            'devices' => $devicesValue,
            'parts' => $partsValue,
            'inventory' => $inventoryValue,
            'consumables' => $consumablesValue,
        ];
    }
}
