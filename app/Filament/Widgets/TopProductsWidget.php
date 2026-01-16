<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TopProductsWidget extends Widget
{
    // Приховуємо на Dashboard - використовуємо нові мінімалістичні віджети
    public static function canView(): bool
    {
        return false;
    }
    
    protected static string $view = 'filament.widgets.top-products-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 3;
    
    public ?string $filter = 'profit';
    
    protected function getFilters(): ?array
    {
        return [
            'profit' => 'За прибутком',
            'revenue' => 'За доходом',
            'quantity' => 'За кількістю продажів',
        ];
    }
    
    public function getTopProducts(): array
    {
        $filter = $this->filter ?? 'profit';
        
        $topProducts = Sale::select(
                'saleable_id',
                'saleable_type',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(sell_price) as total_revenue'),
                DB::raw('SUM(profit) as total_profit'),
                DB::raw('COUNT(*) as sales_count')
            )
            ->groupBy('saleable_id', 'saleable_type')
            ->when($filter === 'profit', fn($q) => $q->orderByRaw('SUM(profit) DESC'))
            ->when($filter === 'revenue', fn($q) => $q->orderByRaw('SUM(sell_price) DESC'))
            ->when($filter === 'quantity', fn($q) => $q->orderByRaw('SUM(quantity) DESC'))
            ->limit(10)
            ->get()
            ->map(function ($sale) {
                // Отримуємо перший Sale запис для цього товару для доступу до saleable
                $firstSale = Sale::where('saleable_id', $sale->saleable_id)
                    ->where('saleable_type', $sale->saleable_type)
                    ->with('saleable')
                    ->first();
                
                return [
                    'name' => $firstSale && $firstSale->saleable ? $this->getProductName($firstSale->saleable) : 'Невідомий товар',
                    'type' => $this->getProductType($sale->saleable_type),
                    'quantity' => (int) $sale->total_quantity,
                    'revenue' => (float) $sale->total_revenue,
                    'profit' => (float) $sale->total_profit,
                    'sales_count' => (int) $sale->sales_count,
                ];
            })
            ->toArray();
        
        return $topProducts;
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
    
    protected function getProductType(string $type): string
    {
        return match($type) {
            'App\\Models\\Device' => 'Техніка',
            'App\\Models\\Part' => 'Деталь',
            'App\\Models\\Accessory' => 'Аксесуар',
            'App\\Models\\Inventory' => 'Інвентар',
            'App\\Models\\Consumable' => 'Розхідник',
            default => 'Інше',
        };
    }
}
