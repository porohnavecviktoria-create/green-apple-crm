<?php

namespace App\Filament\Widgets;

use App\Models\Part;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class InventoryAnalyticsWidget extends BaseWidget
{
    // Приховуємо на Dashboard - використовуємо нові мінімалістичні віджети
    public static function canView(): bool
    {
        return false;
    }
    
    protected static ?int $sort = 4;
    
    protected function getHeading(): string
    {
        return 'Аналітика складу';
    }

    protected function getStats(): array
    {
        // Інвентар
        $inventory = Part::whereHas('partType', function ($query) {
            $query->where('name', 'like', '%Інвентар%');
        });
        $inventoryCount = $inventory->where('status', 'Stock')->sum('quantity');
        $inventoryValue = $inventory->where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        // Аксесуари
        $accessories = Part::whereHas('partType', function ($query) {
            $query->where('name', 'like', '%Аксесуар%');
        });
        $accessoriesCount = $accessories->where('status', 'Stock')->sum('quantity');
        $accessoriesValue = $accessories->where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        // Деталі
        $parts = Part::whereHas('partType', function ($query) {
            $query->where('name', 'not like', '%Аксесуар%')
                ->where('name', 'not like', '%Інвентар%')
                ->where('name', 'not like', '%Розхідник%');
        });
        $partsCount = $parts->where('status', 'Stock')->sum('quantity');
        $partsValue = $parts->where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        // Розхідники
        $consumables = Part::whereHas('partType', function ($query) {
            $query->where('name', 'like', '%Розхідник%');
        });
        $consumablesCount = $consumables->where('status', 'Stock')->sum('quantity');
        $consumablesValue = $consumables->where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        // Загальна вартість складу
        $totalStockValue = $inventoryValue + $accessoriesValue + $partsValue + $consumablesValue;
        $totalStockCount = $inventoryCount + $accessoriesCount + $partsCount + $consumablesCount;

        return [
            Stat::make('Загальна вартість складу', number_format($totalStockValue, 2) . ' грн')
                ->description(number_format($totalStockCount, 0) . ' одиниць на складі')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            
            Stat::make('Інвентар', number_format($inventoryCount, 0) . ' шт.')
                ->description('Вартість: ' . number_format($inventoryValue, 2) . ' грн')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('gray'),
            
            Stat::make('Аксесуари', number_format($accessoriesCount, 0) . ' шт.')
                ->description('Вартість: ' . number_format($accessoriesValue, 2) . ' грн')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('info'),
            
            Stat::make('Деталі', number_format($partsCount, 0) . ' шт.')
                ->description('Вартість: ' . number_format($partsValue, 2) . ' грн')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('warning'),
            
            Stat::make('Розхідники', number_format($consumablesCount, 0) . ' шт.')
                ->description('Вартість: ' . number_format($consumablesValue, 2) . ' грн')
                ->descriptionIcon('heroicon-m-beaker')
                ->color('info'),
        ];
    }
}
