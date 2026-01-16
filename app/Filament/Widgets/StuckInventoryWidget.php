<?php

namespace App\Filament\Widgets;

use App\Models\Device;
use App\Models\Part;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class StuckInventoryWidget extends Widget
{
    // Приховуємо - використовуємо нові мінімалістичні віджети
    public static function canView(): bool
    {
        return false;
    }
    
    protected static string $view = 'filament.widgets.stuck-inventory-widget';
    
    protected int | string | array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];
    
    protected static ?int $sort = 5;
    
    public function getStuckInventory(): array
    {
        $daysThreshold = 14;
        $cutoffDate = now()->subDays($daysThreshold);
        
        // Device на складі 14+ днів
        $stuckDevices = Device::where('status', 'Stock')
            ->where('created_at', '<=', $cutoffDate)
            ->select('model as name', 'purchase_cost as cost', DB::raw('julianday("now") - julianday(created_at) as days_in_stock'))
            ->get()
            ->map(function ($device) {
                return [
                    'name' => $device->name,
                    'days_in_stock' => (int) round($device->days_in_stock),
                    'cost' => (float) $device->cost,
                    'type' => 'Техніка',
                ];
            });
        
        // Part на складі 14+ днів
        $stuckParts = Part::where('status', 'Stock')
            ->where('created_at', '<=', $cutoffDate)
            ->select('name', DB::raw('cost_uah * quantity as cost'), DB::raw('julianday("now") - julianday(created_at) as days_in_stock'))
            ->get()
            ->map(function ($part) {
                return [
                    'name' => $part->name,
                    'days_in_stock' => (int) round($part->days_in_stock),
                    'cost' => (float) $part->cost,
                    'type' => 'Деталь',
                ];
            });
        
        // Об'єднуємо та сортуємо по днях
        $allStuck = $stuckDevices->merge($stuckParts)
            ->sortByDesc('days_in_stock')
            ->values()
            ->take(10)
            ->toArray();
        
        return $allStuck;
    }
}
