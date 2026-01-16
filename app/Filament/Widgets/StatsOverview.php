<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $todaySales = \App\Models\Sale::whereDate('sold_at', today());
        $allSales = \App\Models\Sale::query();

        return [
            \Filament\Widgets\StatsOverviewWidget\Stat::make('Прибуток (за весь час)', number_format($allSales->sum('profit'), 2) . ' грн')
                ->description('Чистий профіт')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            \Filament\Widgets\StatsOverviewWidget\Stat::make('Продажі сьогодні', number_format($todaySales->sum('sell_price'), 2) . ' грн')
                ->description('Оборот за 24 години')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),
            \Filament\Widgets\StatsOverviewWidget\Stat::make('Кількість продажів', $allSales->count())
                ->description('Всього проведено чеків')
                ->descriptionIcon('heroicon-m-clipboard-document-check'),
        ];
    }
}
