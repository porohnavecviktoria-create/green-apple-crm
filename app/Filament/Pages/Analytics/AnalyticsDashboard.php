<?php

namespace App\Filament\Pages\Analytics;

use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\ProfitLossChart;
use App\Filament\Widgets\TopProductsWidget;
use Filament\Pages\Page;

class AnalyticsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    
    protected static string $view = 'filament.pages.analytics.dashboard';
    
    protected static ?string $navigationLabel = 'Аналітика';
    
    protected static ?string $title = 'Аналітика';
    
    protected static ?string $navigationGroup = 'Аналітика';
    
    protected static ?int $navigationSort = 1;
    
    protected function getHeaderWidgets(): array
    {
        return [
            FinancialOverviewWidget::class,
            ProfitLossChart::class,
            TopProductsWidget::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [];
    }
}
