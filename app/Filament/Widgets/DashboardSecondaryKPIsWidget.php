<?php

namespace App\Filament\Widgets;

use App\Models\Device;
use App\Models\Part;
use App\Models\Repair;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class DashboardSecondaryKPIsWidget extends BaseWidget
{
    // Приховуємо на Dashboard - всі картки тепер в DashboardKPIsWidget
    public static function canView(): bool
    {
        return false;
    }
    
    public ?string $filter = 'this_month';
    
    protected static ?int $sort = 2;
    
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
    
    protected function getStats(): array
    {
        $dateRange = $this->getDateRange();
        
        // Заморожені кошти (товари на складі)
        $devicesInStock = (float) Device::where('status', 'Stock')
            ->sum(DB::raw('purchase_cost'));
        
        $partsInStock = (float) Part::where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        $frozenInStock = $devicesInStock + $partsInStock;
        
        // Кількість продажів
        $salesCount = Sale::whereBetween('sold_at', $dateRange)->count();
        
        // Кількість ремонтів
        $repairsCount = Repair::whereBetween('created_at', $dateRange)->count();
        
        $stats = [];
        
        // Показуємо тільки якщо є дані
        if ($frozenInStock > 0) {
            $stats[] = Stat::make('Заморожені кошти', number_format($frozenInStock, 2) . ' грн')
                ->description('Товари на складі')
                ->color('warning');
        }
        
        if ($salesCount > 0 || $repairsCount > 0) {
            $stats[] = Stat::make('Продажі / Ремонти', $salesCount . ' / ' . $repairsCount)
                ->description('Кількість операцій')
                ->color('info');
        }
        
        return $stats;
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
        
        return match($filter) {
            'today' => [$today, $todayEnd],
            '7days' => [$sevenDays, $todayEnd],
            '30days' => [$thirtyDays, $todayEnd],
            'this_month' => [$monthStart, $monthEnd],
            default => [$monthStart, $monthEnd],
        };
    }
}
