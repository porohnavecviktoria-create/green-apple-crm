<?php

namespace App\Filament\Widgets;

use App\Models\Part;
use App\Models\Repair;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ServiceAnalyticsWidget extends Widget
{
    protected static string $view = 'filament.widgets.service-analytics-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 5;
    
    public function getServiceData(): array
    {
        $dateRange = \App\Filament\Widgets\DatePeriodFilterWidget::getDateRange();
        
        // Сервіс за період
        $serviceStats = Repair::whereBetween('created_at', $dateRange)
            ->select(
                DB::raw('SUM(repair_cost) as total_income'),
                DB::raw('SUM(parts_cost) as total_expenses'),
                DB::raw('SUM(profit) as total_profit')
            )
            ->first();
        
        // Загальна сума деталей на складі (Part з типом "Деталь", не Аксесуар/Інвентар/Розхідник)
        $partsInStock = (float) Part::whereHas('partType', function ($query) {
                $query->where('name', 'not like', '%Аксесуар%')
                    ->where('name', 'not like', '%Інвентар%')
                    ->where('name', 'not like', '%Розхідник%')
                    ->where('name', 'not like', '%Викрутка%')
                    ->where('name', 'not like', '%Паяльник%')
                    ->where('name', 'not like', '%Переклей%')
                    ->where('name', 'not like', '%Чохол%');
            })
            ->where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        return [
            'period' => [
                'income' => (float) ($serviceStats->total_income ?? 0),
                'expenses' => (float) ($serviceStats->total_expenses ?? 0),
                'profit' => (float) ($serviceStats->total_profit ?? 0),
            ],
            'parts_in_stock' => $partsInStock,
        ];
    }
}
