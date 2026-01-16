<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Session;

class DatePeriodFilterWidget extends Widget
{
    protected static string $view = 'filament.widgets.date-period-filter-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = -1;
    
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    
    public function mount(): void
    {
        // Встановлюємо за замовчуванням поточний місяць
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
        
        // Зберігаємо в сесії
        Session::put('dashboard_date_from', $this->dateFrom);
        Session::put('dashboard_date_to', $this->dateTo);
    }
    
    public function updatedDateFrom(): void
    {
        if ($this->dateFrom) {
            Session::put('dashboard_date_from', $this->dateFrom);
        }
    }
    
    public function updatedDateTo(): void
    {
        if ($this->dateTo) {
            Session::put('dashboard_date_to', $this->dateTo);
        }
    }
    
    public static function getDateRange(): array
    {
        $dateFrom = Session::get('dashboard_date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = Session::get('dashboard_date_to', now()->endOfMonth()->format('Y-m-d'));
        
        return [
            \Carbon\Carbon::parse($dateFrom)->startOfDay(),
            \Carbon\Carbon::parse($dateTo)->endOfDay(),
        ];
    }
}
