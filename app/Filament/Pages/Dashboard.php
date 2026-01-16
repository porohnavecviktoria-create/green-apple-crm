<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Аналітика';
    
    protected static ?string $title = 'Аналітика';
    
    protected ?string $heading = 'Аналітика';
    
    public function getHeaderWidgetsColumns(): int | array
    {
        return 'full';
    }
    
    public function getFooterWidgetsColumns(): int | array
    {
        return 'full';
    }
}
