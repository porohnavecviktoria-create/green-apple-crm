<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use App\Models\Device;
use App\Models\Order;
use App\Models\Part;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    // Приховуємо - використовуємо нові мінімалістичні віджети
    public static function canView(): bool
    {
        return false;
    }
    
    protected function getStats(): array
    {
        // Продажі
        $todaySales = Sale::whereDate('sold_at', today());
        $allSales = Sale::query();
        $monthSales = Sale::whereMonth('sold_at', now()->month)
            ->whereYear('sold_at', now()->year);
        
        // Гроші в обороті (товари на складі)
        $devicesInStock = Device::where('status', 'Stock')
            ->sum(DB::raw('purchase_cost'));
        
        $partsInStock = Part::where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        $totalInventoryValue = $devicesInStock + $partsInStock;
        
        // Інвентар
        $inventoryCount = Part::whereHas('partType', function ($query) {
            $query->where('name', 'like', '%Інвентар%');
        })->where('status', 'Stock')->sum('quantity');
        
        // Загальна кількість товару
        $totalDevices = Device::where('status', 'Stock')->count();
        $totalParts = Part::where('status', 'Stock')->sum('quantity');
        $totalItems = $totalDevices + $totalParts;
        
        // Списання (товари зі статусом Broken)
        $writtenOffDevices = Device::where('status', 'Broken')->count();
        $writtenOffParts = Part::where('status', 'Broken')->sum('quantity');
        $totalWrittenOff = $writtenOffDevices + $writtenOffParts;
        
        // Закупки (партії)
        $totalBatches = Batch::count();
        $totalPurchases = Batch::sum('total_cost');
        $monthPurchases = Batch::whereMonth('purchase_date', now()->month)
            ->whereYear('purchase_date', now()->year)
            ->sum('total_cost');
        
        // Дохід
        $totalRevenue = $allSales->sum('sell_price');
        $totalProfit = $allSales->sum('profit');
        $monthRevenue = $monthSales->sum('sell_price');
        $monthProfit = $monthSales->sum('profit');

        return [
            Stat::make('Гроші в обороті', number_format($totalInventoryValue, 2) . ' грн')
                ->description('Вартість товарів на складі')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
            
            Stat::make('Загальний дохід', number_format($totalRevenue, 2) . ' грн')
                ->description('Дохід за весь час')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            
            Stat::make('Чистий прибуток', number_format($totalProfit, 2) . ' грн')
                ->description('Прибуток за весь час')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
            
            Stat::make('Дохід цього місяця', number_format($monthRevenue, 2) . ' грн')
                ->description('Оборот за ' . now()->format('m.Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
            
            Stat::make('Товарів на складі', number_format($totalItems, 0))
                ->description($totalDevices . ' техніки, ' . number_format($totalParts, 0) . ' деталей/аксесуарів')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('info'),
            
            Stat::make('Інвентар', number_format($inventoryCount, 0) . ' шт.')
                ->description('Кількість інвентарю на складі')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('gray'),
            
            Stat::make('Списано товарів', number_format($totalWrittenOff, 0))
                ->description($writtenOffDevices . ' техніки, ' . number_format($writtenOffParts, 0) . ' деталей')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
            
            Stat::make('Закупок (партій)', $totalBatches)
                ->description('На суму ' . number_format($totalPurchases, 2) . ' грн')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),
            
            Stat::make('Закупки цього місяця', number_format($monthPurchases, 2) . ' грн')
                ->description('Витрати на закупівлю за ' . now()->format('m.Y'))
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->color('warning'),
        ];
    }
}
