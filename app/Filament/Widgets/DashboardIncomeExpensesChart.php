<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DashboardIncomeExpensesChart extends ChartWidget
{
    // Приховуємо - використовуємо нові мінімалістичні віджети
    public static function canView(): bool
    {
        return false;
    }
    
    protected static ?string $heading = 'Дохід vs Витрати';
    
    protected static ?string $description = 'По днях за обраний період';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
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
    
    protected function getData(): array
    {
        $dateRange = $this->getDateRange();
        $startDate = $dateRange[0];
        $endDate = $dateRange[1];
        
        $days = [];
        $income = [];
        $expenses = [];
        
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $days[] = $currentDate->format('d.m');
            
            // Дохід за день
            $dayIncome = (float) Sale::whereDate('sold_at', $currentDate->format('Y-m-d'))
                ->sum('sell_price');
            $income[] = $dayIncome;
            
            // Витрати за день
            $dayExpenses = (float) Batch::whereDate('purchase_date', $currentDate->format('Y-m-d'))
                ->sum('total_cost');
            $expenses[] = $dayExpenses;
            
            $currentDate->addDay();
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Дохід',
                    'data' => $income,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Витрати',
                    'data' => $expenses,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $days,
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return value.toLocaleString("uk-UA") + " грн"; }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
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
