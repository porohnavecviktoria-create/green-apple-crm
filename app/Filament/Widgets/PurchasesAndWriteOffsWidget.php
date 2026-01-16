<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use App\Models\Device;
use App\Models\Part;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PurchasesAndWriteOffsWidget extends ChartWidget
{
    // Приховуємо на Dashboard - використовуємо нові мінімалістичні віджети
    public static function canView(): bool
    {
        return false;
    }
    
    protected static ?string $heading = 'Закупки та списання за останні 6 місяців';
    
    protected static ?int $sort = 5;

    protected function getData(): array
    {
        // Закупки (партії) - SQLite-сумісний запит
        $purchases = Batch::select(
                DB::raw("CAST(strftime('%m', purchase_date) AS INTEGER) as month"),
                DB::raw("CAST(strftime('%Y', purchase_date) AS INTEGER) as year"),
                DB::raw('SUM(total_cost) as total')
            )
            ->where('purchase_date', '>=', now()->subMonths(6))
            ->whereNotNull('purchase_date')
            ->groupBy(DB::raw("strftime('%Y-%m', purchase_date)"))
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // Списання (товари зі статусом Broken, які були створені в цей період) - SQLite-сумісний запит
        $writeOffs = Part::select(
                DB::raw("CAST(strftime('%m', created_at) AS INTEGER) as month"),
                DB::raw("CAST(strftime('%Y', created_at) AS INTEGER) as year"),
                DB::raw('SUM(cost_uah * quantity) as total')
            )
            ->where('status', 'Broken')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy(DB::raw("strftime('%Y-%m', created_at)"))
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        $labels = [];
        $purchasesData = [];
        $writeOffsData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;
            
            $labels[] = $date->format('M Y');
            
            $purchase = $purchases->first(function ($p) use ($month, $year) {
                return $p->month == $month && $p->year == $year;
            });
            
            $writeOff = $writeOffs->first(function ($w) use ($month, $year) {
                return $w->month == $month && $w->year == $year;
            });
            
            $purchasesData[] = $purchase ? (float) $purchase->total : 0;
            $writeOffsData[] = $writeOff ? (float) $writeOff->total : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Закупки (грн)',
                    'data' => $purchasesData,
                    'backgroundColor' => 'rgba(251, 191, 36, 0.2)',
                    'borderColor' => 'rgb(251, 191, 36)',
                    'fill' => true,
                ],
                [
                    'label' => 'Списання (грн)',
                    'data' => $writeOffsData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
