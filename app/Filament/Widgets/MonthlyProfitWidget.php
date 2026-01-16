<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use App\Models\Device;
use App\Models\Part;
use App\Models\Repair;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MonthlyProfitWidget extends BaseWidget
{
    // Приховуємо - використовуємо тільки таблиці без карток
    public static function canView(): bool
    {
        return false;
    }
    
    protected static ?int $sort = 1;
    
    protected function getColumns(): int
    {
        return 1;
    }
    
    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        
        // Всі доходи за місяць (продажі)
        $allIncome = (float) Sale::whereBetween('sold_at', [$monthStart, $monthEnd])
            ->sum('sell_price');
        
        // Всі витрати за місяць (закупки - партії)
        $allExpenses = (float) Batch::whereBetween('purchase_date', [$monthStart, $monthEnd])
            ->sum('total_cost');
        
        // Списання за місяць (Device зі статусом Broken, створені в цьому місяці)
        $writeOffDevices = (float) Device::where('status', 'Broken')
            ->whereBetween('updated_at', [$monthStart, $monthEnd])
            ->sum('purchase_cost');
        
        // Списання Parts за місяць (Part зі статусом Broken, створені в цьому місяці)
        // Рахуємо тільки ті, що були списані (quantity * cost_uah для тих що мали quantity > 0)
        $writeOffParts = (float) Part::where('status', 'Broken')
            ->whereBetween('updated_at', [$monthStart, $monthEnd])
            ->sum(DB::raw('cost_uah * quantity'));
        
        $totalWriteOffs = $writeOffDevices + $writeOffParts;
        
        // Витрати сервісу за місяць (деталі/списання в ремонти)
        $serviceExpenses = (float) Repair::whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('parts_cost');
        
        // Прибуток цього місяця = дохід - витрати - списання - витрати сервісу
        $monthlyProfit = $allIncome - $allExpenses - $totalWriteOffs - $serviceExpenses;
        
        return [
            Stat::make('Прибуток цього місяця', number_format($monthlyProfit, 2) . ' грн')
                ->description('Дохід - Витрати - Списання - Витрати сервісу')
                ->color($monthlyProfit >= 0 ? 'success' : 'danger'),
        ];
    }
}
