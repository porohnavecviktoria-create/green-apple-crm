<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use App\Models\Device;
use App\Models\Part;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FinancialOverviewWidget extends BaseWidget
{
    // Приховуємо на Dashboard - використовуємо нові мінімалістичні віджети
    public static function canView(): bool
    {
        return false;
    }
    
    protected function getStats(): array
    {
        // Дохід
        $allSales = Sale::query();
        $todaySales = Sale::whereDate('sold_at', today());
        $weekSales = Sale::whereBetween('sold_at', [now()->startOfWeek(), now()->endOfWeek()]);
        $monthSales = Sale::where(DB::raw("strftime('%m', sold_at)"), now()->format('m'))
            ->where(DB::raw("strftime('%Y', sold_at)"), now()->format('Y'));
        $yearSales = Sale::where(DB::raw("strftime('%Y', sold_at)"), now()->format('Y'));
        
        $totalRevenue = (float) $allSales->sum('sell_price');
        $totalProfit = (float) $allSales->sum('profit');
        $todayRevenue = (float) $todaySales->sum('sell_price');
        $weekRevenue = (float) $weekSales->sum('sell_price');
        $monthRevenue = (float) $monthSales->sum('sell_price');
        $yearRevenue = (float) $yearSales->sum('sell_price');
        
        $todayProfit = (float) $todaySales->sum('profit');
        $weekProfit = (float) $weekSales->sum('profit');
        $monthProfit = (float) $monthSales->sum('profit');
        $yearProfit = (float) $yearSales->sum('profit');
        
        // Витрати (закупки + списання)
        $allBatches = Batch::query();
        $monthBatches = Batch::where(DB::raw("strftime('%m', purchase_date)"), now()->format('m'))
            ->where(DB::raw("strftime('%Y', purchase_date)"), now()->format('Y'));
        $yearBatches = Batch::where(DB::raw("strftime('%Y', purchase_date)"), now()->format('Y'));
        
        $totalPurchases = (float) $allBatches->sum('total_cost');
        $monthPurchases = (float) $monthBatches->sum('total_cost');
        $yearPurchases = (float) $yearBatches->sum('total_cost');
        
        // Списання (товари зі статусом Broken або Scrap)
        $writtenOffDevices = Device::where('status', 'Scrap')
            ->orWhere('status', 'Broken')
            ->sum(DB::raw('purchase_cost'));
        $writtenOffParts = Part::where('status', 'Broken')
            ->sum(DB::raw('cost_uah * quantity'));
        $totalWriteOffs = (float) ($writtenOffDevices + $writtenOffParts);
        
        // Загальні витрати
        $totalExpenses = $totalPurchases + $totalWriteOffs;
        $monthExpenses = $monthPurchases;
        $yearExpenses = $yearPurchases;
        
        // Маржинальність
        $totalMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue * 100) : 0;
        $monthMargin = $monthRevenue > 0 ? ($monthProfit / $monthRevenue * 100) : 0;
        
        // Гроші в обороті (товари на складі)
        $devicesInStock = (float) Device::where('status', 'Stock')
            ->sum(DB::raw('purchase_cost'));
        
        $partsInStock = (float) Part::where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        $totalInventoryValue = $devicesInStock + $partsInStock;
        
        // Чистий прибуток (дохід - витрати)
        $netProfit = $totalRevenue - $totalExpenses;
        $monthNetProfit = $monthRevenue - $monthExpenses;
        
        return [
            Stat::make('Загальний дохід', number_format($totalRevenue, 2) . ' грн')
                ->description('Всього за весь час')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([$todayRevenue, $weekRevenue, $monthRevenue, $yearRevenue]),
            
            Stat::make('Загальні витрати', number_format($totalExpenses, 2) . ' грн')
                ->description('Закупки: ' . number_format($totalPurchases, 2) . ' грн, Списання: ' . number_format($totalWriteOffs, 2) . ' грн')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            
            Stat::make('Чистий прибуток', number_format($netProfit, 2) . ' грн')
                ->description($netProfit >= 0 ? 'Дохід перевищує витрати' : 'Витрати перевищують дохід')
                ->descriptionIcon($netProfit >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($netProfit >= 0 ? 'success' : 'danger'),
            
            Stat::make('Маржинальність', number_format($totalMargin, 2) . '%')
                ->description('Прибуток від доходу')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($totalMargin >= 20 ? 'success' : ($totalMargin >= 10 ? 'warning' : 'danger'))
                ->chart([$totalMargin]),
            
            Stat::make('Дохід цього місяця', number_format($monthRevenue, 2) . ' грн')
                ->description('Прибуток: ' . number_format($monthProfit, 2) . ' грн (' . number_format($monthMargin, 2) . '%)')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
            
            Stat::make('Прибуток цього місяця', number_format($monthNetProfit, 2) . ' грн')
                ->description('Після вирахування витрат')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($monthNetProfit >= 0 ? 'success' : 'danger'),
            
            Stat::make('Гроші в обороті', number_format($totalInventoryValue, 2) . ' грн')
                ->description('Вартість товарів на складі')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('warning'),
            
            Stat::make('Витрати цього місяця', number_format($monthExpenses, 2) . ' грн')
                ->description('Закупки за ' . now()->format('m.Y'))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning'),
        ];
    }
}
