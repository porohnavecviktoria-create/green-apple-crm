<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use App\Models\Device;
use App\Models\Part;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class CashFlowWidget extends BaseWidget
{
    // Приховуємо на Dashboard - використовуємо нові мінімалістичні віджети
    public static function canView(): bool
    {
        return false;
    }
    
    protected static ?int $sort = 3;
    
    protected function getStats(): array
    {
        // Гроші в обороті (товари на складі)
        $devicesInStock = (float) Device::where('status', 'Stock')
            ->sum(DB::raw('purchase_cost'));
        
        $partsInStock = (float) Part::where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        $accessoriesInStock = (float) Part::whereHas('partType', function ($query) {
            $query->where('name', 'like', '%Аксесуар%');
        })
            ->where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        $inventoryInStock = (float) Part::whereHas('partType', function ($query) {
            $query->where('name', 'like', '%Інвентар%');
        })
            ->where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        $consumablesInStock = (float) Part::whereHas('partType', function ($query) {
            $query->where('name', 'like', '%Розхідник%');
        })
            ->where('status', 'Stock')
            ->sum(DB::raw('cost_uah * quantity'));
        
        $totalInventoryValue = $devicesInStock + $partsInStock + $accessoriesInStock + $inventoryInStock + $consumablesInStock;
        
        // Дохід за місяць
        $monthRevenue = (float) Sale::where(DB::raw("strftime('%m', sold_at)"), now()->format('m'))
            ->where(DB::raw("strftime('%Y', sold_at)"), now()->format('Y'))
            ->sum('sell_price');
        
        // Витрати за місяць
        $monthPurchases = (float) Batch::where(DB::raw("strftime('%m', purchase_date)"), now()->format('m'))
            ->where(DB::raw("strftime('%Y', purchase_date)"), now()->format('Y'))
            ->sum('total_cost');
        
        // Вільні кошти (приблизно - дохід мінус витрати мінус товари на складі)
        // У реальній системі це має бути рахунок, але тут використаємо як оцінку
        $estimatedCash = $monthRevenue - $monthPurchases;
        
        // Дебіторська заборгованість (покупки з неоплаченими статусами - якщо такі є)
        // Поки що приймаємо, що всі покупки оплачені
        $accountsReceivable = 0;
        
        // Кредиторська заборгованість (неоплачені партії - якщо такі є)
        // Поки що приймаємо, що всі партії оплачені
        $accountsPayable = 0;
        
        return [
            Stat::make('Гроші в обороті', number_format($totalInventoryValue, 2) . ' грн')
                ->description(
                    'Техніка: ' . number_format($devicesInStock, 2) . ' грн, ' .
                    'Деталі: ' . number_format($partsInStock, 2) . ' грн, ' .
                    'Аксесуари: ' . number_format($accessoriesInStock, 2) . ' грн'
                )
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('warning'),
            
            Stat::make('Дохід за місяць', number_format($monthRevenue, 2) . ' грн')
                ->description('Вхідні грошові потоки')
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->color('success'),
            
            Stat::make('Витрати за місяць', number_format($monthPurchases, 2) . ' грн')
                ->description('Вихідні грошові потоки')
                ->descriptionIcon('heroicon-m-arrow-up-circle')
                ->color('danger'),
            
            Stat::make('Чистий грошовий потік', number_format($estimatedCash, 2) . ' грн')
                ->description($estimatedCash >= 0 ? 'Позитивний потік' : 'Негативний потік')
                ->descriptionIcon($estimatedCash >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($estimatedCash >= 0 ? 'success' : 'danger'),
            
            Stat::make('Дебіторська заборгованість', number_format($accountsReceivable, 2) . ' грн')
                ->description('Кошти, які мають отримати')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
            
            Stat::make('Кредиторська заборгованість', number_format($accountsPayable, 2) . ' грн')
                ->description('Кошти, які мають сплатити')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }
}
