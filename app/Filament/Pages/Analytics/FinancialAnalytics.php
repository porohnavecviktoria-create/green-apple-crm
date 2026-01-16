<?php

namespace App\Filament\Pages\Analytics;

use App\Filament\Widgets\CashFlowWidget;
use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\ProfitLossChart;
use Filament\Pages\Page;

class FinancialAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static string $view = 'filament.pages.analytics.financial';
    
    protected static ?string $navigationLabel = 'Фінансова аналітика';
    
    protected static ?string $title = 'Фінансова аналітика';
    
    protected static ?string $navigationGroup = 'Аналітика';
    
    protected static ?int $navigationSort = 2;
    
    protected function getHeaderWidgets(): array
    {
        return [
            FinancialOverviewWidget::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            ProfitLossChart::class,
            CashFlowWidget::class,
        ];
    }
}
