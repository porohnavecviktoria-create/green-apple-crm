<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TopSalesWidget extends Widget
{
    // Приховуємо - використовуємо нові мінімалістичні віджети
    public static function canView(): bool
    {
        return false;
    }
    
    protected static string $view = 'filament.widgets.top-sales-widget';
    
    protected int | string | array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];
    
    protected static ?int $sort = 4;
    
    public ?string $filter = 'this_month';
    
    protected function getFilters(): ?array
    {
        return [
            'today' => 'Сьогодні',
            '7days' => '7 днів',
            '30days' => '30 днів',
            'this_month' => 'Цей місяць',
            'custom' => 'Довільний період',
        ];
    }
    
    public function getTopSales(): array
    {
        $dateRange = $this->getDateRange();
        
        $topSales = Sale::whereBetween('sold_at', $dateRange)
            ->select(
                'saleable_id',
                'saleable_type',
                DB::raw('SUM(sell_price) as total_revenue'),
                DB::raw('SUM(profit) as total_profit'),
                DB::raw('COUNT(*) as sales_count')
            )
            ->groupBy('saleable_id', 'saleable_type')
            ->orderByRaw('SUM(profit) DESC')
            ->limit(5)
            ->get()
            ->map(function ($sale) {
                // Отримуємо перший Sale запис для доступу до saleable
                $firstSale = Sale::where('saleable_id', $sale->saleable_id)
                    ->where('saleable_type', $sale->saleable_type)
                    ->with('saleable')
                    ->first();
                
                return [
                    'name' => $firstSale && $firstSale->saleable ? $this->getProductName($firstSale->saleable) : 'Невідомий товар',
                    'revenue' => (float) $sale->total_revenue,
                    'profit' => (float) $sale->total_profit,
                ];
            })
            ->toArray();
        
        return $topSales;
    }
    
    protected function getProductName($product): string
    {
        if (method_exists($product, 'getDisplayName')) {
            return $product->getDisplayName();
        }
        
        if (property_exists($product, 'name')) {
            return $product->name;
        }
        
        if (property_exists($product, 'model')) {
            return $product->model;
        }
        
        return 'Невідомий товар';
    }
    
    protected function getDateRange(): array
    {
        $filter = $this->filter ?? 'this_month';
        
        $today = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $sevenDays = now()->subDays(7)->startOfDay();
        $thirtyDays = now()->subDays(30)->startOfDay();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        
        if ($filter === 'today') {
            return [$today, $todayEnd];
        } elseif ($filter === '7days') {
            return [$sevenDays, $todayEnd];
        } elseif ($filter === '30days') {
            return [$thirtyDays, $todayEnd];
        } else {
            return [$monthStart, $monthEnd];
        }
    }
}
