<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use App\Models\Device;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BatchAnalyticsWidget extends BaseWidget
{
    // Приховуємо на Dashboard - використовуємо нові мінімалістичні віджети
    public static function canView(): bool
    {
        return false;
    }
    
    protected static ?int $sort = 3;
    
    protected function getHeading(): string
    {
        return 'Аналітика по партіях';
    }

    protected function getStats(): array
    {
        // Загальна статистика по партіях
        $totalBatches = Batch::count();
        $totalBatchesCost = Batch::sum('total_cost');
        
        // Партії цього місяця
        $monthBatches = Batch::whereMonth('purchase_date', now()->month)
            ->whereYear('purchase_date', now()->year)
            ->count();
        $monthBatchesCost = Batch::whereMonth('purchase_date', now()->month)
            ->whereYear('purchase_date', now()->year)
            ->sum('total_cost');
        
        // Техніка з партій на складі
        $devicesInBatches = Device::whereHas('batch')
            ->where('status', 'Stock')
            ->count();
        $devicesInBatchesValue = Device::whereHas('batch')
            ->where('status', 'Stock')
            ->sum('purchase_cost');
        
        // Продана техніка з партій
        $soldDevicesFromBatches = Device::whereHas('batch')
            ->where('status', 'Sold')
            ->count();
        
        // Середня вартість партії
        $avgBatchCost = $totalBatches > 0 ? $totalBatchesCost / $totalBatches : 0;

        return [
            Stat::make('Всього партій', $totalBatches)
                ->description('На суму ' . number_format($totalBatchesCost, 2) . ' грн')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('info'),
            
            Stat::make('Партій цього місяця', $monthBatches)
                ->description('На суму ' . number_format($monthBatchesCost, 2) . ' грн')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),
            
            Stat::make('Техніки з партій на складі', $devicesInBatches)
                ->description('Вартість: ' . number_format($devicesInBatchesValue, 2) . ' грн')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('warning'),
            
            Stat::make('Продано техніки з партій', $soldDevicesFromBatches)
                ->description('Всього продано одиниць')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Середня вартість партії', number_format($avgBatchCost, 2) . ' грн')
                ->description('Середня сума закупівлі')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('gray'),
        ];
    }
}
