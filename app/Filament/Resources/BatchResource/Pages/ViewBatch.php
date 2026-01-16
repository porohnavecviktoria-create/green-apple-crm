<?php

namespace App\Filament\Resources\BatchResource\Pages;

use App\Filament\Resources\BatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewBatch extends ViewRecord
{
    protected static string $resource = BatchResource::class;

    protected static ?string $title = 'Перегляд партії';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Редагувати'),
            Actions\DeleteAction::make()
                ->label('Видалити'),
        ];
    }

    protected function mutateRecordForView($record)
    {
        // Завантажуємо зв'язки для оптимізації з усіма потрібними полями
        $record->load([
            'devices' => function ($query) {
                $query->select('id', 'batch_id', 'model', 'marker', 'imei', 'lock_status', 'description', 'purchase_cost', 'additional_costs', 'status', 'selling_price');
            },
            'devices.sales',
            'contractor'
        ]);
        return $record;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Інформація про партію')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Назва партії')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('purchase_date')
                                    ->label('Дата поступлення')
                                    ->date('d.m.Y')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                            ]),
                        Infolists\Components\TextEntry::make('contractor.name')
                            ->label('Контрагент')
                            ->default('Не вказано'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Опис')
                            ->default('Немає опису')
                            ->columnSpanFull(),
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('devices_count')
                                    ->label('Кількість пристроїв')
                                    ->formatStateUsing(fn($record) => $record->devices->count())
                                    ->badge()
                                    ->color('info')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium),
                                Infolists\Components\TextEntry::make('total_purchase_cost')
                                    ->label('Собівартість')
                                    ->money('UAH')
                                    ->formatStateUsing(function ($record) {
                                        return $record->devices->sum('purchase_cost');
                                    })
                                    ->color('gray')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium),
                                Infolists\Components\TextEntry::make('total_expenses')
                                    ->label('Загальна сума витрат')
                                    ->money('UAH')
                                    ->formatStateUsing(function ($record) {
                                        $purchaseCost = $record->devices->sum('purchase_cost');
                                        $additionalCosts = $record->devices->sum('additional_costs');
                                        return $purchaseCost + $additionalCosts;
                                    })
                                    ->weight('bold')
                                    ->color('gray')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium),
                            ]),
                    ]),

                Infolists\Components\Section::make('Аналітика партії')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                // Загальна ціна партії
                                Infolists\Components\TextEntry::make('total_batch_cost')
                                    ->label('Загальна ціна партії')
                                    ->state(function ($record) {
                                        $purchaseCost = (float)($record->devices->sum('purchase_cost') ?? 0);
                                        $additionalCosts = (float)($record->devices->sum('additional_costs') ?? 0);
                                        $total = $purchaseCost + $additionalCosts;
                                        return number_format($total, 2, '.', ' ') . ' грн';
                                    })
                                    ->weight('bold')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                                
                                // Сума продажів
                                Infolists\Components\TextEntry::make('total_sales_amount')
                                    ->label('На яку суму продано')
                                    ->state(function ($record) {
                                        $totalSales = 0;
                                        foreach ($record->devices as $device) {
                                            $device->load('sales');
                                            $totalSales += (float)($device->sales->sum('sell_price') ?? 0);
                                        }
                                        return number_format($totalSales, 2, '.', ' ') . ' грн';
                                    })
                                    ->weight('bold')
                                    ->color('success')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                                
                                // Прибуток
                                Infolists\Components\TextEntry::make('batch_profit')
                                    ->label('Прибуток')
                                    ->state(function ($record) {
                                        $totalSales = 0;
                                        $totalCostSold = 0;
                                        
                                        foreach ($record->devices as $device) {
                                            $device->load('sales');
                                            $deviceSales = (float)($device->sales->sum('sell_price') ?? 0);
                                            if ($deviceSales > 0) {
                                                // Товар продано - рахуємо собівартість
                                                $purchaseCost = (float)($device->purchase_cost ?? 0);
                                                $additionalCosts = (float)($device->additional_costs ?? 0);
                                                $totalCostSold += $purchaseCost + $additionalCosts;
                                            }
                                            $totalSales += $deviceSales;
                                        }
                                        
                                        $profit = $totalSales - $totalCostSold;
                                        return number_format($profit, 2, '.', ' ') . ' грн';
                                    })
                                    ->weight('bold')
                                    ->color(function ($record) {
                                        $totalSales = 0;
                                        $totalCostSold = 0;
                                        
                                        foreach ($record->devices as $device) {
                                            $device->load('sales');
                                            $deviceSales = (float)($device->sales->sum('sell_price') ?? 0);
                                            if ($deviceSales > 0) {
                                                $purchaseCost = (float)($device->purchase_cost ?? 0);
                                                $additionalCosts = (float)($device->additional_costs ?? 0);
                                                $totalCostSold += $purchaseCost + $additionalCosts;
                                            }
                                            $totalSales += $deviceSales;
                                        }
                                        
                                        return ($totalSales - $totalCostSold) >= 0 ? 'success' : 'danger';
                                    })
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                                
                                // Сума товарів що залишились
                                Infolists\Components\TextEntry::make('remaining_stock_value')
                                    ->label('На яку суму залишилось товарів')
                                    ->state(function ($record) {
                                        $remainingDevices = $record->devices->where('status', 'Stock');
                                        $totalValue = 0;
                                        
                                        foreach ($remainingDevices as $device) {
                                            $purchaseCost = (float)($device->purchase_cost ?? 0);
                                            $additionalCosts = (float)($device->additional_costs ?? 0);
                                            $totalValue += $purchaseCost + $additionalCosts;
                                        }
                                        
                                        return number_format($totalValue, 2, '.', ' ') . ' грн';
                                    })
                                    ->weight('bold')
                                    ->color('gray')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                            ]),
                    ]),

                Infolists\Components\Section::make('Пристрої в партії')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('devices')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        Infolists\Components\Grid::make(5)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('model')
                                                    ->label('Модель')
                                                    ->weight('bold')
                                                    ->columnSpan(2),
                                                Infolists\Components\TextEntry::make('marker')
                                                    ->label('Маркер')
                                                    ->default('—')
                                                    ->badge()
                                                    ->color('info'),
                                                Infolists\Components\TextEntry::make('imei')
                                                    ->label('IMEI')
                                                    ->default('—')
                                                    ->badge()
                                                    ->color('info'),
                                                Infolists\Components\TextEntry::make('lock_status')
                                                    ->label('Блокування')
                                                    ->formatStateUsing(function ($state) {
                                                        return match($state) {
                                                            'unlock' => 'Розблоковано',
                                                            'lock' => 'Заблоковано',
                                                            'mdm' => 'MDM',
                                                            'bypass' => 'Bypass',
                                                            default => '—'
                                                        };
                                                    })
                                                    ->badge()
                                                    ->color(fn($state) => match($state) {
                                                        'unlock' => 'success',
                                                        'lock' => 'danger',
                                                        'mdm' => 'warning',
                                                        'bypass' => 'info',
                                                        default => 'gray'
                                                    }),
                                            ]),
                                        Infolists\Components\Grid::make(4)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('purchase_cost')
                                                    ->label('Собівартість')
                                                    ->money('UAH')
                                                    ->default(0)
                                                    ->color('gray'),
                                                Infolists\Components\TextEntry::make('additional_costs')
                                                    ->label('Витрати')
                                                    ->money('UAH')
                                                    ->default(0)
                                                    ->color('gray'),
                                                Infolists\Components\TextEntry::make('total_cost')
                                                    ->label('Вартість')
                                                    ->money('UAH')
                                                    ->formatStateUsing(function ($record) {
                                                        $purchaseCost = (float)($record->purchase_cost ?? 0);
                                                        $additionalCosts = (float)($record->additional_costs ?? 0);
                                                        return $purchaseCost + $additionalCosts;
                                                    })
                                                    ->weight('bold')
                                                    ->color('gray')
                                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium),
                                                Infolists\Components\TextEntry::make('edit_link')
                                                    ->label('')
                                                    ->formatStateUsing(function ($record) {
                                                        $url = \App\Filament\Resources\DeviceResource::getUrl('edit', ['record' => $record]);
                                                        return new \Illuminate\Support\HtmlString(
                                                            '<a href="' . $url . '" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-primary-600 bg-primary-50 border border-primary-300 rounded-md hover:bg-primary-100 transition-colors">
                                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                </svg>
                                                                Редагувати
                                                            </a>'
                                                        );
                                                    })
                                                    ->alignEnd(),
                                            ]),
                                    ]),
                                
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Коментар')
                                    ->default('—')
                                    ->columnSpanFull()
                                    ->placeholder('Немає коментаря'),
                            ])
                            ->columns(1),
                    ]),

                Infolists\Components\Section::make('Товари що залишились на складі')
                    ->schema([
                        Infolists\Components\TextEntry::make('remaining_devices_list')
                            ->label('')
                            ->state(function ($record) {
                                $remainingDevices = $record->devices->where('status', 'Stock');
                                
                                if ($remainingDevices->isEmpty()) {
                                    return 'Всі товари продано або списано';
                                }
                                
                                $html = '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse">';
                                $html .= '<thead><tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">';
                                $html .= '<th class="px-4 py-2 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Модель</th>';
                                $html .= '<th class="px-4 py-2 text-left text-xs font-bold text-gray-700 dark:text-gray-300">IMEI</th>';
                                $html .= '<th class="px-4 py-2 text-right text-xs font-bold text-gray-700 dark:text-gray-300">Собівартість</th>';
                                $html .= '<th class="px-4 py-2 text-right text-xs font-bold text-gray-700 dark:text-gray-300">Витрати</th>';
                                $html .= '<th class="px-4 py-2 text-right text-xs font-bold text-gray-700 dark:text-gray-300">Вартість</th>';
                                $html .= '</tr></thead><tbody>';
                                
                                foreach ($remainingDevices as $device) {
                                    $purchaseCost = (float)($device->purchase_cost ?? 0);
                                    $additionalCosts = (float)($device->additional_costs ?? 0);
                                    $totalCost = $purchaseCost + $additionalCosts;
                                    
                                    $html .= '<tr class="border-b border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50">';
                                    $html .= '<td class="px-4 py-2 font-medium text-gray-900 dark:text-white">' . e($device->model ?? '—') . '</td>';
                                    $html .= '<td class="px-4 py-2 text-gray-600 dark:text-gray-400">' . e($device->imei ?? '—') . '</td>';
                                    $html .= '<td class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">' . number_format($purchaseCost, 2) . ' грн</td>';
                                    $html .= '<td class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">' . number_format($additionalCosts, 2) . ' грн</td>';
                                    $html .= '<td class="px-4 py-2 text-right font-bold text-gray-900 dark:text-white">' . number_format($totalCost, 2) . ' грн</td>';
                                    $html .= '</tr>';
                                }
                                
                                $html .= '</tbody></table></div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn($record) => $record->devices->where('status', 'Stock')->isNotEmpty()),
            ]);
    }
}
