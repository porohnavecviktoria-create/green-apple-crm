<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use App\Models\Device;
use App\Models\Part;
use App\Models\Repair;
use App\Models\Sale;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class BusinessSummaryWidget extends Widget
{
    protected static string $view = 'filament.widgets.business-summary-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1;
    
    public function getBusinessData(): array
    {
        $dateRange = \App\Filament\Widgets\DatePeriodFilterWidget::getDateRange();
        
        // Загальний дохід бізнесу
        // Техніка + аксесуари
        $deviceSales = Sale::where('saleable_type', Device::class)
            ->whereBetween('sold_at', $dateRange)
            ->sum('sell_price');
        
        $accessoryPartIds = Part::whereHas('partType', function ($query) {
                $query->where('name', 'like', '%Аксесуар%');
            })
            ->pluck('id')
            ->toArray();
        
        $accessorySales = Sale::where('saleable_type', Part::class)
            ->whereIn('saleable_id', $accessoryPartIds)
            ->whereBetween('sold_at', $dateRange)
            ->sum('sell_price');
        
        // Ремонти (дохід сервісу)
        $repairIncome = Repair::whereBetween('created_at', $dateRange)
            ->sum('repair_cost');
        
        $totalIncome = (float) $deviceSales + (float) $accessorySales + (float) $repairIncome;
        
        // Загальні розходи
        // Закупки (партії)
        $purchases = Batch::whereBetween('purchase_date', $dateRange)
            ->sum('total_cost');
        
        // Витрати деталей в ремонти
        $repairPartsCost = Repair::whereBetween('created_at', $dateRange)
            ->sum('parts_cost');
        
        // Списання товарів (Device зі статусом Broken)
        $writeOffDevices = Device::where('status', 'Broken')
            ->whereBetween('updated_at', $dateRange)
            ->sum('purchase_cost');
        
        // Списання Parts (Broken)
        // Враховуємо як нові списані деталі (created_at з назвою "(Списано)"), так і існуючі що стали Broken (updated_at з описом "Списано:")
        $writeOffParts = Part::where('status', 'Broken')
            ->where(function ($query) use ($dateRange) {
                $query->where(function ($q) use ($dateRange) {
                    // Нові списані деталі (часткове списання - створюються з назвою "(Списано)")
                    $q->whereBetween('created_at', $dateRange)
                      ->where('name', 'like', '%(Списано)%');
                })->orWhere(function ($q) use ($dateRange) {
                    // Існуючі деталі що стали Broken (повне списання - оновлюються з описом "Списано:")
                    $q->whereBetween('updated_at', $dateRange)
                      ->where(function ($subQ) {
                          $subQ->where('description', 'like', '%Списано:%')
                               ->orWhere('description', 'like', '%Списано з:%');
                      });
                });
            })
            ->get()
            ->sum(function ($part) use ($dateRange) {
                // Якщо quantity > 0, використовуємо його, інакше - беремо cost_uah як суму списання
                // (коли quantity = 0, це означає що списали всю кількість, тому беремо повну вартість)
                if ($part->quantity > 0) {
                    return $part->cost_uah * $part->quantity;
                }
                // Для повного списання (quantity = 0), беремо cost_uah як суму списаної деталі
                return $part->cost_uah;
            });
        
        // Списання розхідників (Part з PartType "Розхідник" + Broken)
        $consumablePartIds = Part::whereHas('partType', function ($query) {
                $query->where('name', 'like', '%Розхідник%');
            })
            ->pluck('id')
            ->toArray();
        
        $writeOffConsumables = Part::where('status', 'Broken')
            ->whereIn('id', $consumablePartIds)
            ->where(function ($query) use ($dateRange) {
                $query->where(function ($q) use ($dateRange) {
                    $q->whereBetween('created_at', $dateRange)
                      ->where('name', 'like', '%(Списано)%');
                })->orWhere(function ($q) use ($dateRange) {
                    $q->whereBetween('updated_at', $dateRange)
                      ->where(function ($subQ) {
                          $subQ->where('description', 'like', '%Списано:%')
                               ->orWhere('description', 'like', '%Списано з:%');
                      });
                });
            })
            ->get()
            ->sum(function ($part) {
                if ($part->quantity > 0) {
                    return $part->cost_uah * $part->quantity;
                }
                return $part->cost_uah;
            });
        
        $totalExpenses = (float) $purchases + (float) $repairPartsCost + (float) $writeOffDevices + (float) $writeOffParts + (float) $writeOffConsumables;
        
        // Прибуток
        $profit = $totalIncome - $totalExpenses;
        
        return [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'profit' => $profit,
        ];
    }
}
