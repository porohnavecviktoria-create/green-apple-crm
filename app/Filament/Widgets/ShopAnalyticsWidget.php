<?php

namespace App\Filament\Widgets;

use App\Models\Device;
use App\Models\Part;
use App\Models\Repair;
use App\Models\Sale;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ShopAnalyticsWidget extends Widget
{
    protected static string $view = 'filament.widgets.shop-analytics-widget';
    
    protected int | string | array $columnSpan = [
        'md' => 12,
        'xl' => 12,
    ];
    
    protected static ?int $sort = 3;
    
    public function getShopData(): array
    {
        $dateRange = \App\Filament\Widgets\DatePeriodFilterWidget::getDateRange();
        
        // Техніка за період
        $deviceSales = Sale::where('saleable_type', Device::class)
            ->whereBetween('sold_at', $dateRange)
            ->select(
                DB::raw('SUM(sell_price) as total_sales'),
                DB::raw('SUM(buy_price) as total_cost'),
                DB::raw('SUM(profit) as total_profit')
            )
            ->first();
        
        // Аксесуари за період - спочатку отримуємо ID аксесуарів
        $accessoryPartIds = Part::whereHas('partType', function ($query) {
                $query->where('name', 'like', '%Аксесуар%');
            })
            ->pluck('id')
            ->toArray();
        
        $accessorySales = Sale::where('saleable_type', Part::class)
            ->whereIn('saleable_id', $accessoryPartIds)
            ->whereBetween('sold_at', $dateRange)
            ->select(
                DB::raw('SUM(sell_price) as total_sales'),
                DB::raw('SUM(buy_price) as total_cost'),
                DB::raw('SUM(profit) as total_profit')
            )
            ->first();
        
        // Ремонти за період
        $repairStats = Repair::whereBetween('created_at', $dateRange)
            ->select(
                DB::raw('SUM(repair_cost) as total_sales'),
                DB::raw('SUM(parts_cost) as total_cost'),
                DB::raw('SUM(profit) as total_profit')
            )
            ->first();
        
        return [
            'devices' => [
                'sales' => (float) ($deviceSales->total_sales ?? 0),
                'cost' => (float) ($deviceSales->total_cost ?? 0),
                'profit' => (float) ($deviceSales->total_profit ?? 0),
            ],
            'accessories' => [
                'sales' => (float) ($accessorySales->total_sales ?? 0),
                'cost' => (float) ($accessorySales->total_cost ?? 0),
                'profit' => (float) ($accessorySales->total_profit ?? 0),
            ],
            'repairs' => [
                'sales' => (float) ($repairStats->total_sales ?? 0),
                'cost' => (float) ($repairStats->total_cost ?? 0),
                'profit' => (float) ($repairStats->total_profit ?? 0),
            ],
        ];
    }
}
