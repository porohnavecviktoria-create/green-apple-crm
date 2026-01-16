<?php

namespace App\Filament\Widgets;

use App\Models\Device;
use App\Models\Part;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class BottomSummaryWidget extends Widget
{
    protected static string $view = 'filament.widgets.bottom-summary-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 6;
    
    public function getSummaryData(): array
    {
        $dateRange = \App\Filament\Widgets\DatePeriodFilterWidget::getDateRange();
        
        // Списання товарів за період (Device зі статусом Broken, створені/оновлені в періоді)
        $writeOffDevices = (float) Device::where('status', 'Broken')
            ->whereBetween('updated_at', $dateRange)
            ->sum('purchase_cost');
        
        // Списання Parts за період (Part зі статусом Broken)
        // Враховуємо як нові списані деталі (created_at), так і існуючі що стали Broken (updated_at)
        $writeOffParts = (float) Part::where('status', 'Broken')
            ->where(function ($query) use ($dateRange) {
                $query->whereBetween('created_at', $dateRange) // Нові списані деталі (часткове списання)
                    ->orWhere(function ($q) use ($dateRange) {
                        // Існуючі деталі що стали Broken (повне списання)
                        $q->whereBetween('updated_at', $dateRange)
                          ->where(function ($subQ) {
                              $subQ->where('name', 'like', '%(Списано)%')
                                  ->orWhere(function ($descQ) {
                                      $descQ->whereNotNull('description')
                                            ->where('description', 'like', '%Списано%');
                                  });
                          });
                    });
            })
            ->sum(DB::raw('cost_uah * quantity'));
        
        $totalWriteOffs = $writeOffDevices + $writeOffParts;
        
        // Інвентар в наявності зараз (Part з PartType "Інвентар" + status Stock)
        $inventoryInStock = (float) Part::whereHas('partType', function ($query) {
                $query->where('name', 'like', '%Інвентар%');
            })
            ->where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        return [
            'write_offs' => $totalWriteOffs,
            'inventory_in_stock' => $inventoryInStock,
        ];
    }
}
