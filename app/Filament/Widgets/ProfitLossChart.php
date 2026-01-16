<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use App\Models\Device;
use App\Models\Part;
use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ProfitLossChart extends ChartWidget
{
    protected static ?string $heading = 'Графік прибуток/збиток';
    
    protected static ?string $description = 'Дохід та витрати за останні 12 місяців';
    
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';
    
    public ?string $filter = '12months';
    
    protected function getFilters(): ?array
    {
        return [
            '6months' => 'Останні 6 місяців',
            '12months' => 'Останні 12 місяців',
            'year' => 'Цей рік',
        ];
    }
    
    protected function getData(): array
    {
        $months = [];
        $revenue = [];
        $expenses = [];
        $profit = [];
        
        // Визначаємо початок періоду
        $startDate = match($this->filter) {
            '6months' => now()->subMonths(6)->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->subMonths(12)->startOfMonth(),
        };
        
        $endDate = now()->endOfMonth();
        
        // Генеруємо дані для кожного місяця
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $monthKey = $currentDate->format('Y-m');
            $months[] = $currentDate->format('M Y');
            
            // Дохід за місяць
            $monthRevenue = (float) Sale::where(DB::raw("strftime('%Y-%m', sold_at)"), $currentDate->format('Y-m'))
                ->sum('sell_price');
            $revenue[] = $monthRevenue;
            
            // Витрати за місяць (закупки + списання)
            $monthPurchases = (float) Batch::where(DB::raw("strftime('%Y-%m', purchase_date)"), $currentDate->format('Y-m'))
                ->sum('total_cost');
            
            // Списання за місяць (приблизно, використовуємо created_at для записів зі статусом Scrap/Broken)
            $writtenOffDevices = (float) Device::whereIn('status', ['Scrap', 'Broken'])
                ->where(DB::raw("strftime('%Y-%m', updated_at)"), $currentDate->format('Y-m'))
                ->sum(DB::raw('purchase_cost'));
            
            $writtenOffParts = (float) Part::where('status', 'Broken')
                ->where(DB::raw("strftime('%Y-%m', updated_at)"), $currentDate->format('Y-m'))
                ->sum(DB::raw('cost_uah * quantity'));
            
            $monthWriteOffs = $writtenOffDevices + $writtenOffParts;
            $monthExpenses = $monthPurchases + $monthWriteOffs;
            $expenses[] = $monthExpenses;
            
            // Прибуток
            $monthProfit = $monthRevenue - $monthExpenses;
            $profit[] = $monthProfit;
            
            $currentDate->addMonth();
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Дохід',
                    'data' => $revenue,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                ],
                [
                    'label' => 'Витрати',
                    'data' => $expenses,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'fill' => true,
                ],
                [
                    'label' => 'Прибуток',
                    'data' => $profit,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderDash' => [5, 5],
                    'fill' => false,
                ],
            ],
            'labels' => $months,
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
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                    'ticks' => [
                        'callback' => 'function(value) { return value.toLocaleString("uk-UA") + " грн"; }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
