<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SalesChart extends ChartWidget
{
    // Приховуємо на Dashboard - використовуємо нові мінімалістичні віджети
    public static function canView(): bool
    {
        return false;
    }
    
    protected static ?string $heading = 'Продажі за останні 12 місяців';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // SQLite-сумісний запит
        $sales = Sale::select(
                DB::raw("CAST(strftime('%m', sold_at) AS INTEGER) as month"),
                DB::raw("CAST(strftime('%Y', sold_at) AS INTEGER) as year"),
                DB::raw('SUM(sell_price) as total_revenue'),
                DB::raw('SUM(profit) as total_profit')
            )
            ->where('sold_at', '>=', now()->subMonths(12))
            ->whereNotNull('sold_at')
            ->groupBy(DB::raw("strftime('%Y-%m', sold_at)"))
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        $labels = [];
        $revenueData = [];
        $profitData = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;
            
            $labels[] = $date->format('M Y');
            
            $sale = $sales->first(function ($s) use ($month, $year) {
                return $s->month == $month && $s->year == $year;
            });
            
            $revenueData[] = $sale ? (float) $sale->total_revenue : 0;
            $profitData[] = $sale ? (float) $sale->total_profit : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Дохід (грн)',
                    'data' => $revenueData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                ],
                [
                    'label' => 'Прибуток (грн)',
                    'data' => $profitData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
